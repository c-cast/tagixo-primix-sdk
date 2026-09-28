<?php

use Illuminate\Support\Facades\Route;
use Tagixo\FormBuilder\FormBuilder;
use Tagixo\Primix\Capabilities\FormBuilderCapability;
use Tagixo\Primix\Forms\PropTypes\BooleanTablePropType;
use Tagixo\Primix\Forms\PropTypes\DateTablePropType;
use Tagixo\Primix\Forms\PropTypes\FileTablePropType;
use Tagixo\Primix\Forms\PropTypes\PrimixTablePropType;
use Tagixo\Primix\Resources\FormResource;
use Tagixo\Primix\TagixoPrimixPlugin;
use Tagixo\Primix\Tests\Support\TestPanelProvider;

/*
 * With the form builder installed the capability applies itself: the `app` form
 * target, the table tab of every field, and the preview of an app form inside the
 * panel.
 */

it('applies itself and gives forms their admin resource', function () {
    expect(app(FormBuilderCapability::class)->available())->toBeTrue()
        ->and(array_map(
            static fn ($capability): string => $capability->id(),
            app(TagixoPrimixPlugin::class)->resolveCapabilities(),
        ))->toContain('form-builder')
        ->and(app(TagixoPrimixPlugin::class)->resolveResources())->toHaveKey('forms');
});

it('enables the app form target, which no public page could render', function () {
    expect(app(FormBuilder::class)->appFormsEnabled())->toBeTrue()
        ->and(app(FormBuilder::class)->getEnabledFormTargets())->toContain('app');
});

it('gives every field a table tab, and the right one per field', function () {
    $forms = app(FormBuilder::class);

    expect($forms->getFormModuleExtensions('text-input'))->toBe(['table' => PrimixTablePropType::class])
        ->and($forms->getFormModuleExtensions('checkbox'))->toBe(['table' => BooleanTablePropType::class])
        ->and($forms->getFormModuleExtensions('date-picker'))->toBe(['table' => DateTablePropType::class])
        ->and($forms->getFormModuleExtensions('file-upload'))->toBe(['table' => FileTablePropType::class])
        // A panel form is sized by its layout.
        ->and($forms->getFormModuleHiddenPropTypes('text-input'))->toContain('sizing');
});

it('sends the builder preview of an app form to the panel page', function () {
    $forms = app(FormBuilder::class);

    expect($forms->hasAppFormPreviewer())->toBeTrue()
        ->and($forms->resolveAppFormPreviewUrl(7))
        ->toBe(FormResource::getUrl('preview-app', ['record' => 7]))
        ->and(Route::has('primix.admin.forms.preview-app'))->toBeTrue();
});

it('locks the target when the panel asks, and leaves the choice otherwise', function () {
    expect(app(FormBuilder::class)->getLockedFormTarget())->toBeNull();

    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->lockFormTarget('app');
    $this->refreshApplication();

    expect(app(FormBuilder::class)->getLockedFormTarget())->toBe('app');
});

it('does nothing at all when the panel drops the capability', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->withoutCapability('form-builder');
    $this->refreshApplication();

    $ids = array_map(
        static fn ($capability): string => $capability->id(),
        app(TagixoPrimixPlugin::class)->resolveCapabilities(),
    );

    expect($ids)->not->toContain('form-builder')
        ->and(app(FormBuilder::class)->appFormsEnabled())->toBeFalse()
        ->and(app(FormBuilder::class)->hasAppFormPreviewer())->toBeFalse()
        // The records are still administered: a capability is not a resource.
        ->and(app(TagixoPrimixPlugin::class)->resolveResources())->toHaveKey('forms');
});
