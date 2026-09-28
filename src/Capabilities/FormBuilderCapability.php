<?php

namespace Tagixo\Primix\Capabilities;

use Primix\Panel;
use Tagixo\FormBuilder\FormBuilder;
use Tagixo\Primix\Forms\PropTypes\BooleanTablePropType;
use Tagixo\Primix\Forms\PropTypes\DateTablePropType;
use Tagixo\Primix\Forms\PropTypes\FileTablePropType;
use Tagixo\Primix\Forms\PropTypes\PrimixTablePropType;
use Tagixo\Primix\Resources\FormResource;
use Tagixo\Primix\TagixoPrimixPlugin;
use Throwable;

/**
 * What the panel gains from the form builder.
 *
 * A Primix panel is exactly the place where the interactive layouts of a form —
 * tabs, wizard, groups — are first class, so the `app` form target is enabled
 * here: without an SDK the builder only offers the universal palette, which is
 * what a public page can render. Each field also gains a `table` tab, so the
 * form says how its answers look in a listing, and the builder's own Preview of
 * an app form opens the panel's page instead of the HTML one.
 */
class FormBuilderCapability implements Capability
{
    public function id(): string
    {
        return 'form-builder';
    }

    public function available(): bool
    {
        return app()->bound(FormBuilder::class);
    }

    public function apply(Panel $panel, TagixoPrimixPlugin $plugin): void
    {
        $forms = app(FormBuilder::class);

        if (($locked = $plugin->lockedFormTarget()) !== null) {
            // Locking to 'app' enables that target on its own.
            $forms->lockFormTarget($locked);
        } elseif ($plugin->appFormsEnabled()) {
            $forms->enableAppForms();
        }

        // How a field's answers appear in a Primix table, declared on the field.
        $forms->extendFormModule('*', ['table' => PrimixTablePropType::class]);
        $forms->extendFormModule(['checkbox'], ['table' => BooleanTablePropType::class]);
        $forms->extendFormModule(['date-picker'], ['table' => DateTablePropType::class]);
        $forms->extendFormModule(['file-upload'], ['table' => FileTablePropType::class]);

        // A panel form is sized by its layout, not by the field.
        $forms->hideFormModulePropTypes('*', ['sizing']);

        $forms->registerAppFormPreviewer(static function (int|string $id): ?string {
            try {
                return FormResource::getUrl('preview-app', ['record' => $id]);
            } catch (Throwable) {
                // No panel route for forms (the type is not administered here).
                return null;
            }
        });
    }
}
