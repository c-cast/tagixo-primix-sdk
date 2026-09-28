<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Primix\Tables\Columns\BadgeColumn;
use Primix\Tables\Columns\IconColumn;
use Primix\Tables\Columns\TextColumn;
use Primix\Tables\Filters\SelectFilter;
use Primix\Tables\Filters\TernaryFilter;
use Tagixo\Core\Services\BuilderApiService;
use Tagixo\FormBuilder\FormBuilder;
use Tagixo\FormBuilder\Models\FormSchema;
use Tagixo\FormBuilder\Modules\TextInputModule;
use Tagixo\Primix\Forms\PrimixFormColumns;
use Tagixo\Primix\Forms\PrimixFormFilters;
use Tagixo\Primix\Forms\PropTypes\PrimixTablePropType;

/*
 * A field says how its answers look in a listing: the capability gives every
 * form module a `table` tab, and these helpers turn what the editor set there
 * into Primix columns and filters.
 */

uses(RefreshDatabase::class);

function tableForm(array $components): FormSchema
{
    return FormSchema::create([
        'title' => 'Signups',
        'slug' => 'signups',
        'status' => 'published',
        'content' => ['body' => [], 'components' => $components],
        'fields' => $components,
    ]);
}

function tableField(string $id, string $type, array $content, array $table, int $order = 0): array
{
    return [
        'id' => $id,
        'type' => $type,
        'parent_id' => null,
        'order' => $order,
        'props' => ['content' => $content, 'table' => $table],
    ];
}

it('adds the table tab to a form module, once the target is the panel', function () {
    app(FormBuilder::class)->setCurrentFormTarget('app');

    expect(TextInputModule::getPropTypes())->toBe(['table' => PrimixTablePropType::class])
        // Sizing is the layout's business in a panel.
        ->and(TextInputModule::designProps())->not->toContain('sizing');
});

it('offers that tab only to a form the panel renders', function () {
    app(FormBuilder::class)->setCurrentFormTarget('universal');

    // A public page renders its own HTML: a Primix column would mean nothing.
    expect(TextInputModule::getPropTypes())->toBe([])
        ->and(TextInputModule::designProps())->toContain('sizing');
});

it('builds the columns the fields asked for', function () {
    tableForm([
        tableField('a', 'text-input', ['name' => 'email', 'label' => 'Email'], [
            'show_in_table' => true,
            'sortable' => true,
            'searchable' => true,
        ]),
        tableField('b', 'select', ['name' => 'plan', 'label' => 'Plan'], [
            'show_in_table' => true,
            'column_type' => 'badge',
            'column_label' => 'Subscription',
        ], 1),
        tableField('c', 'checkbox', ['name' => 'newsletter', 'label' => 'Newsletter'], [
            'show_in_table' => true,
            'column_type' => 'boolean',
        ], 2),
        // Left out on purpose: not every answer belongs in a listing.
        tableField('d', 'text-area', ['name' => 'notes', 'label' => 'Notes'], ['show_in_table' => false], 3),
    ]);

    $columns = PrimixFormColumns::from('signups');

    expect($columns)->toHaveCount(3)
        ->and($columns[0])->toBeInstanceOf(TextColumn::class)
        ->and($columns[0]->getName())->toBe('email')
        ->and($columns[0]->getLabel())->toBe('Email')
        ->and($columns[0]->isSortable())->toBeTrue()
        ->and($columns[0]->isSearchable())->toBeTrue()
        ->and($columns[1])->toBeInstanceOf(BadgeColumn::class)
        ->and($columns[1]->getLabel())->toBe('Subscription')
        ->and($columns[2])->toBeInstanceOf(IconColumn::class)
        ->and($columns[2]->isBoolean())->toBeTrue();
});

it('builds a filter from the options of a select', function () {
    tableForm([
        tableField('a', 'select', [
            'name' => 'plan',
            'label' => 'Plan',
            'options' => [['value' => 'free', 'label' => 'Free'], ['value' => 'pro', 'label' => 'Pro']],
        ], ['show_in_table' => true, 'filterable' => true]),
    ]);

    $filters = PrimixFormFilters::from('signups');

    expect($filters)->toHaveCount(1)
        ->and($filters[0])->toBeInstanceOf(SelectFilter::class)
        ->and($filters[0]->getName())->toBe('plan')
        ->and($filters[0]->getOptions())->toBe(['free' => 'Free', 'pro' => 'Pro']);
});

it('leaves free text to the search box instead of inventing a filter', function () {
    tableForm([
        tableField('a', 'text-input', ['name' => 'email', 'label' => 'Email'], [
            'show_in_table' => true,
            'filterable' => true,
            'searchable' => true,
        ]),
        tableField('b', 'checkbox', ['name' => 'newsletter', 'label' => 'Newsletter'], [
            'show_in_table' => true,
            'filterable' => true,
        ], 1),
    ]);

    $filters = PrimixFormFilters::from('signups');

    // Only the checkbox: a list of every e-mail ever typed is no filter.
    expect($filters)->toHaveCount(1)
        ->and($filters[0])->toBeInstanceOf(TernaryFilter::class)
        ->and($filters[0]->getName())->toBe('newsletter');
});

it('gives nothing when no field asks for a column', function () {
    tableForm([tableField('a', 'text-input', ['name' => 'email', 'label' => 'Email'], [])]);

    expect(PrimixFormColumns::from('signups'))->toBe([])
        ->and(PrimixFormFilters::from('signups'))->toBe([]);
});

it('serialises that tab into the editor payload, fields and all', function () {
    app(FormBuilder::class)->setCurrentFormTarget('app');

    $components = app(BuilderApiService::class)->getAvailableComponents('form');
    $textInput = collect($components)->firstWhere('type', 'text-input');

    $table = $textInput['propTypes']['table'] ?? null;

    expect($table)->not->toBeNull()
        ->and($table['type'])->toBe('dynamic')
        ->and($table['schema']['key'])->toBe('table')
        ->and($table['schema']['tab'])->toBe('table')
        ->and(array_column($table['schema']['fields'], 'key'))
        ->toContain('show_in_table', 'column_label', 'column_type', 'sortable', 'searchable');
});
