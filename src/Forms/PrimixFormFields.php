<?php

namespace Tagixo\Primix\Forms;

use Primix\Support\SchemaBuilder;
use Tagixo\FormBuilder\Models\FormSchema;
use Tagixo\Primix\Support\FormSchemaToPrimix;

/**
 * The fields of a form designed in the builder, as Primix form components: drop
 * them into a resource form, a page or a modal and the form the editor drew is
 * the form the panel shows.
 *
 *   $form->schema(PrimixFormFields::from('contact-us'))
 */
class PrimixFormFields
{
    /**
     * @return array<int, object>
     */
    public static function from(string $formSlug): array
    {
        $form = FormSchema::where('slug', $formSlug)->first();

        return $form ? static::resolveFields($form) : [];
    }

    /**
     * @return array<int, object>
     */
    public static function forForm(int|string $formId): array
    {
        $form = FormSchema::find($formId);

        return $form ? static::resolveFields($form) : [];
    }

    /**
     * @return array<int, object>
     */
    protected static function resolveFields(FormSchema $form): array
    {
        return app(SchemaBuilder::class)->build(
            app(FormSchemaToPrimix::class)->fromForm($form),
            'field',
        );
    }
}
