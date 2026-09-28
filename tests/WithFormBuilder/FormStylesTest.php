<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tagixo\FormBuilder\Models\FormSchema;
use Tagixo\Primix\Forms\PrimixFormStyles;

/*
 * The fields keep the look the editor gave them when the panel renders them: the
 * element styles of the builder become CSS scoped to each field wrapper, which
 * `PrimixFormFields` marks with `data-tgx-field`.
 */

uses(RefreshDatabase::class);

function styledForm(array $elements): FormSchema
{
    $components = [[
        'id' => 'a',
        'type' => 'text-input',
        'parent_id' => null,
        'order' => 0,
        'props' => ['content' => ['name' => 'email', 'label' => 'Email'], 'elements' => $elements],
    ]];

    return FormSchema::create([
        'title' => 'Styled',
        'slug' => 'styled',
        'status' => 'published',
        'content' => ['body' => [], 'components' => $components],
        'fields' => $components,
    ]);
}

it('scopes the element styles to the field wrapper Primix renders', function () {
    styledForm([
        'label' => ['typography' => ['font_weight' => '700']],
        'input' => ['typography' => ['font_weight' => '500']],
    ]);

    $css = PrimixFormStyles::from('styled');

    expect($css)->toContain('[data-tgx-field="email"] label')
        ->toContain('font-weight: 700')
        ->toContain('[data-tgx-field="email"] input')
        ->toContain('font-weight: 500')
        // The input selector covers what a Primix field can render.
        ->toContain('textarea')
        ->toContain('select');
});

it('wraps that CSS in a script that survives a LiVue morph', function () {
    styledForm(['label' => ['typography' => ['font_weight' => '700']]]);

    $script = PrimixFormStyles::scriptFrom('styled');

    expect($script)->toStartWith('<script>')
        ->toContain('tgx-form-styles-styled')
        // Idempotent: a second render must not add a second style tag.
        ->toContain('if(document.getElementById(id))return');
});

it('gives nothing when no field was styled', function () {
    styledForm([]);

    expect(PrimixFormStyles::from('styled'))->toBe('')
        ->and(PrimixFormStyles::scriptFrom('styled'))->toBe('')
        ->and(PrimixFormStyles::from('nope'))->toBe('');
});
