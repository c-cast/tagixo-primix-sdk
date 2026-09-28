<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Primix\Forms\Components\Fields\Select;
use Primix\Forms\Components\Fields\TextInput;
use Tagixo\FormBuilder\Models\FormSchema;
use Tagixo\Primix\Forms\PrimixFormColumns;
use Tagixo\Primix\Forms\PrimixFormFields;
use Tagixo\Primix\Support\FormSchemaToPrimix;

/*
 * A form drawn in the builder becomes a real Primix form: the panel is where its
 * interactive layouts (tabs, wizard) are native, which is the whole reason the
 * `app` target exists.
 */

uses(RefreshDatabase::class);

function bridgeForm(array $components, array $body = []): FormSchema
{
    return FormSchema::create([
        'title' => 'Contact us',
        'slug' => 'contact-us',
        'status' => 'published',
        'content' => ['body' => $body, 'components' => $components],
        'fields' => $components,
    ]);
}

function field(string $id, string $type, array $content, array $props = [], ?string $parent = null, int $order = 0): array
{
    return [
        'id' => $id,
        'type' => $type,
        'parent_id' => $parent,
        'order' => $order,
        'props' => ['content' => $content, ...$props],
    ];
}

it('maps each field to its Primix counterpart, with what the editor typed', function () {
    $form = bridgeForm([
        field('a', 'text-input', [
            'name' => 'Full name',
            'label' => 'Full name',
            'placeholder' => 'Jane Doe',
            'helper_text' => 'As on your passport',
        ], ['validation' => ['required' => true]]),
        field('b', 'text-area', ['name' => 'message', 'label' => 'Message'], [], null, 1),
        field('c', 'select', [
            'name' => 'topic',
            'label' => 'Topic',
            'options' => [['value' => 'sales', 'label' => 'Sales'], ['value' => 'support', 'label' => 'Support']],
        ], [], null, 2),
        // A submit button belongs to a public form, not to a panel one.
        field('d', 'submit-button', ['label' => 'Send'], [], null, 3),
    ]);

    $definitions = app(FormSchemaToPrimix::class)->fromForm($form);

    // Everything is wrapped in the grid the body declares (12 columns by default).
    expect($definitions)->toHaveCount(1)
        ->and($definitions[0]['type'])->toBe('grid')
        ->and($definitions[0]['columns'])->toBe(12);

    $fields = $definitions[0]['schema'];

    expect(array_column($fields, 'type'))->toBe(['text-input', 'textarea', 'select'])
        ->and($fields[0]['name'])->toBe('full_name')
        ->and($fields[0]['label'])->toBe('Full name')
        ->and($fields[0]['placeholder'])->toBe('Jane Doe')
        ->and($fields[0]['helperText'])->toBe('As on your passport')
        ->and($fields[0]['required'])->toBeTrue()
        ->and($fields[0]['extraWrapperAttributes'])->toBe(['data-tgx-field' => 'full_name'])
        ->and($fields[2]['options'])->toBe(['sales' => 'Sales', 'support' => 'Support']);
});

it('keeps the layout the editor drew, prefix and all', function () {
    $form = bridgeForm([
        field('g', 'form-grid', ['columns' => 2]),
        field('a', 'text-input', ['name' => 'first', 'label' => 'First', 'column_span' => 1], [], 'g'),
        field('b', 'text-input', ['name' => 'last', 'label' => 'Last', 'column_span' => 1], [], 'g', 1),
        field('s', 'form-section', ['label' => 'Details'], [], null, 1),
        field('c', 'text-area', ['name' => 'notes', 'label' => 'Notes'], [], 's'),
    ], ['grid' => ['columns' => ['value' => 4]]]);

    $schema = app(FormSchemaToPrimix::class)->fromForm($form)[0];

    expect($schema['columns'])->toBe(4);

    [$grid, $section] = $schema['schema'];

    expect($grid['type'])->toBe('grid')
        ->and($grid['columns'])->toBe(2)
        ->and(array_column($grid['schema'], 'name'))->toBe(['first', 'last'])
        ->and($grid['schema'][0]['columnSpan'])->toBe(1)
        ->and($section['type'])->toBe('section')
        ->and($section['label'])->toBe('Details')
        ->and($section['schema'][0]['name'])->toBe('notes');
});

it('turns tabs and a wizard into the native ones, each branch with its own name', function () {
    $form = bridgeForm([
        field('t', 'form-tabs', ['label' => 'Sections']),
        field('t1', 'form-tab', ['label' => 'Who'], [], 't'),
        field('t1a', 'text-input', ['name' => 'who', 'label' => 'Who'], [], 't1'),
        // Unlabelled on purpose: two tabs must not collapse onto one name.
        field('t2', 'form-tab', [], [], 't', 1),
        field('w', 'form-wizard', ['label' => 'Steps'], [], null, 1),
        field('w1', 'form-wizard-step', ['label' => 'One'], [], 'w'),
        field('w1a', 'text-input', ['name' => 'step_one', 'label' => 'One'], [], 'w1'),
    ]);

    $schema = app(FormSchemaToPrimix::class)->fromForm($form)[0]['schema'];

    expect($schema[0]['type'])->toBe('tabs')
        ->and($schema[0]['tabs'])->toHaveCount(2)
        ->and($schema[0]['tabs'][0]['label'])->toBe('Who')
        ->and($schema[0]['tabs'][0]['schema'][0]['name'])->toBe('who')
        ->and($schema[0]['tabs'][1]['label'])->toBe('Tab')
        ->and($schema[0]['tabs'][0]['name'])->not->toBe($schema[0]['tabs'][1]['name'])
        ->and($schema[1]['type'])->toBe('wizard')
        ->and($schema[1]['steps'][0]['label'])->toBe('One')
        ->and($schema[1]['steps'][0]['schema'][0]['name'])->toBe('step_one');
});

it('builds real Primix components from a saved form', function () {
    bridgeForm([
        field('a', 'text-input', ['name' => 'email', 'label' => 'Email']),
        field('b', 'select', ['name' => 'topic', 'label' => 'Topic', 'options' => [['value' => 'sales', 'label' => 'Sales']]], [], null, 1),
    ]);

    $components = PrimixFormFields::from('contact-us');

    expect($components)->toHaveCount(1);

    $inside = $components[0]->getChildComponents();

    expect($inside[0])->toBeInstanceOf(TextInput::class)
        ->and($inside[0]->getName())->toBe('email')
        ->and($inside[1])->toBeInstanceOf(Select::class)
        ->and($inside[1]->getOptions())->toBe(['sales' => 'Sales']);
});

it('gives nothing for a form that does not exist', function () {
    expect(PrimixFormFields::from('nope'))->toBe([])
        ->and(PrimixFormColumns::from('nope'))->toBe([]);
});
