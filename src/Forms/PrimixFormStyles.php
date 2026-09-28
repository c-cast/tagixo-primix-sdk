<?php

namespace Tagixo\Primix\Forms;

use Tagixo\FormBuilder\Models\FormSchema;
use Tagixo\FormBuilder\Support\FormElementsCssGenerator;

/**
 * Generates scoped CSS from form builder element styles for fields rendered
 * inside Primix. Inject the returned string via a <style> tag in the
 * resource/page view so element styling from the builder is applied.
 *
 * Usage in a Primix resource:
 *   protected function getHeaderWidgets(): array { return []; }
 *   // In the form page header (e.g. via getFormContentFooter or a custom view):
 *   new HtmlString('<style>' . PrimixFormStyles::from('form-slug') . '</style>')
 */
class PrimixFormStyles
{
    /**
     * Primix field-wrapper DOM structure:
     *   .primix-field[data-tgx-field="name"]
     *     label  (or .primix-inline-label)
     *     input / textarea / select
     *     .primix-field-helper
     */
    protected const SELECTOR_MAP = [
        'label' => 'label',
        'input' => 'input, textarea, select',
        'helper' => '.primix-field-helper',
        'placeholder' => 'input::placeholder, textarea::placeholder',
        'checkbox_input' => 'input[type="checkbox"]',
        'radio_input' => 'input[type="radio"]',
        'select_input' => 'select',
    ];

    public static function from(string $formSlug): string
    {
        $form = FormSchema::where('slug', $formSlug)->first();

        return $form ? self::resolve($form) : '';
    }

    public static function forForm(int|string $formId): string
    {
        $form = FormSchema::find($formId);

        return $form ? self::resolve($form) : '';
    }

    /**
     * Returns a <script> snippet that injects the form's element styles into
     * <head> via JavaScript. Use this in render hooks so the style survives
     * component morphing (the <head> is outside the LiVue morph area).
     *
     * The script is idempotent: it skips injection if a <style> with the same
     * id already exists (safe to call on repeated renders).
     */
    public static function scriptFrom(string $formSlug): string
    {
        return static::buildScript($formSlug, static::from($formSlug));
    }

    public static function scriptForForm(int|string $formId): string
    {
        return static::buildScript((string) $formId, static::forForm($formId));
    }

    public static function fromFields(array $fields, string $key = 'custom'): string
    {
        return FormElementsCssGenerator::forComponents($fields, static::SELECTOR_MAP);
    }

    public static function scriptFromFields(array $fields, string $key = 'custom'): string
    {
        return static::buildScript($key, static::fromFields($fields, $key));
    }

    private static function buildScript(string $key, string $css): string
    {
        if ($css === '') {
            return '';
        }
        $styleId = json_encode('tgx-form-styles-'.$key);
        $encodedCss = json_encode($css, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return "<script>(function(){var id={$styleId};if(document.getElementById(id))return;var s=document.createElement('style');s.id=id;s.textContent={$encodedCss};document.head.appendChild(s);})();</script>";
    }

    private static function resolve(FormSchema $form): string
    {
        return FormElementsCssGenerator::forForm($form, static::SELECTOR_MAP);
    }
}
