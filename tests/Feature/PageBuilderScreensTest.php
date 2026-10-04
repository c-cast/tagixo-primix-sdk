<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Factories\UserFactory;
use Primix\Forms\Form;
use Primix\Tables\Table;
use Tagixo\PageBuilder\Models\Layout;
use Tagixo\PageBuilder\Models\SiteSettings as Settings;
use Tagixo\Primix\Pages\SiteSettings;
use Tagixo\Primix\Resources\LayoutResource;
use Tagixo\Primix\Resources\PageResource;
use Tagixo\Primix\TagixoPrimixPlugin;

/*
 * Layouts and site settings are not builder types — a layout's content is edited
 * from a page, and the settings are values — so the page builder brings them as
 * screens of its own.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(UserFactory::new()->create());
});

it('administers the layouts and carries the settings, because the page builder is here', function () {
    expect(array_map(
        static fn ($capability): string => $capability->id(),
        app(TagixoPrimixPlugin::class)->resolveCapabilities(),
    ))->toContain('page-builder')
        ->and(Route::has('primix.admin.layouts.index'))->toBeTrue()
        ->and(Route::has('primix.admin.layouts.edit'))->toBeTrue()
        ->and(Route::has('primix.admin.site-settings'))->toBeTrue();
});

it('says what a layout applies to, in the words of the page builder', function () {
    $page = PageResource::createRecord(['title' => 'About us']);

    Layout::create(['name' => 'Fallback', 'is_global' => true, 'conditions' => []]);
    Layout::create([
        'name' => 'About',
        'conditions' => [['type' => 'page_id', 'value' => $page->getKey(), 'label' => 'About us'], ['type' => 'homepage']],
    ]);

    $summary = collect(LayoutResource::table(new Table)->getColumns())
        ->firstWhere(fn ($column): bool => $column->getName() === 'conditions');

    expect($summary->getState(Layout::where('name', 'About')->first()))->toBe('About us, Homepage')
        // The global layout claims nothing and says so.
        ->and($summary->getState(Layout::where('name', 'Fallback')->first()))->toBe('Nothing yet');
});

it('shows the global layout first, then the others by name', function () {
    Layout::create(['name' => 'Zebra', 'conditions' => []]);
    Layout::create(['name' => 'Alpha', 'conditions' => []]);
    Layout::create(['name' => 'Global', 'is_global' => true, 'conditions' => []]);

    expect(LayoutResource::getEloquentQuery()->pluck('name')->all())->toBe(['Global', 'Alpha', 'Zebra']);
});

it('asks what a template claims as five plain questions', function () {
    $page = PageResource::createRecord(['title' => 'About us']);

    $fields = collect(LayoutResource::form(new Form)->getComponents())->keyBy->getName();

    expect($fields->keys()->all())->toBe([
        'name', 'is_global',
        'condition_all_pages', 'condition_homepage', 'condition_pages', 'condition_models', 'condition_model_archives',
    ])
        // Pages are picked several at a time: a template dresses more than one.
        ->and($fields['condition_pages']->isMultiple())->toBeTrue()
        ->and(LayoutResource::pageOptions())->toBe([$page->getKey() => 'About us'])
        // Asked when the template is created, too: a template with no
        // conditions dresses nothing, so there is nothing to come back for.
        ->and($fields['condition_pages']->operation('create')->isHidden())->toBeFalse()
        ->and($fields['condition_pages']->operation('edit')->isHidden())->toBeFalse()
        // An unanswered list is the empty list: left null, the select refuses
        // its own state and the template cannot be created at all.
        ->and($fields['condition_pages']->getDefaultValue())->toBe([])
        ->and($fields['condition_models']->getDefaultValue())->toBe([])
        ->and($fields['condition_model_archives']->getDefaultValue())->toBe([]);
});

it('reads the conditions of a layout as those answers', function () {
    $answers = LayoutResource::conditionsToForm([
        ['type' => 'homepage'],
        ['type' => 'page_id', 'value' => 7],
        ['type' => 'page_id', 'value' => 9],
        ['type' => 'model_all', 'model' => 'articles'],
        ['type' => 'model_archive', 'model' => 'products'],
        // Assigned elsewhere: this screen does not ask about it.
        ['type' => 'model_record', 'model' => 'articles', 'model_id' => 3],
    ]);

    expect($answers)->toBe([
        'condition_all_pages' => false,
        'condition_homepage' => true,
        'condition_pages' => [7, 9],
        'condition_models' => ['articles'],
        'condition_model_archives' => ['products'],
    ]);
});

it('writes them back, and keeps what it never asked about', function () {
    $existing = [
        ['type' => 'homepage'],
        ['type' => 'model_record', 'model' => 'articles', 'model_id' => 3],
    ];

    $conditions = LayoutResource::conditionsFromForm([
        'condition_all_pages' => false,
        'condition_homepage' => false,
        'condition_pages' => [7, 9],
        'condition_models' => [],
        'condition_model_archives' => ['products'],
    ], $existing);

    expect($conditions)->toBe([
        ['type' => 'page_id', 'value' => 7],
        ['type' => 'page_id', 'value' => 9],
        ['type' => 'model_archive', 'model' => 'products'],
        // Untouched: a form must not delete what it cannot see.
        ['type' => 'model_record', 'model' => 'articles', 'model_id' => 3],
    ]);
});

it('takes a template through the form and back', function () {
    $page = PageResource::createRecord(['title' => 'About us']);
    $layout = Layout::create(['name' => 'Shop', 'conditions' => [['type' => 'homepage']]]);

    LayoutResource::updateRecord($layout, [
        'name' => 'Shop',
        ...LayoutResource::conditionsToForm($layout->conditions),
        'condition_homepage' => false,
        'condition_pages' => [$page->getKey()],
    ]);

    expect($layout->refresh()->conditions)->toBe([['type' => 'page_id', 'value' => $page->getKey()]])
        ->and(LayoutResource::conditionsToForm($layout->conditions)['condition_pages'])->toBe([$page->getKey()]);
});

it('keeps the layouts out of the navigation, because the Theme Builder is the way in', function () {
    expect(LayoutResource::shouldRegisterNavigation())->toBeFalse()
        // The screens are still there: the Theme Builder links to them.
        ->and(Route::has('primix.admin.layouts.edit'))->toBeTrue();
});

it('renders the layouts screen with what is there', function () {
    Layout::create(['name' => 'Shop', 'conditions' => [['type' => 'homepage']]]);

    $this->get(LayoutResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Shop')
        ->assertSee('Homepage');
});

it('carries the settings of the site, and only the keys it declares', function () {
    Settings::set('site_name', 'Tagixo');

    $this->get(SiteSettings::getUrl())
        ->assertOk()
        ->assertSee('Site settings')
        ->assertSee('Tagixo');

    expect(array_keys(Settings::settings()))->toBe(Settings::KEYS);
});

it('stores what the form sent, clearing what was emptied and ignoring the rest', function () {
    Settings::set('default_title', 'Old title');

    SiteSettings::store([
        'site_name' => '  Tagixo  ',
        'default_title' => '',
        'custom_css' => 'body { color: #111; }',
        // Not one of the declared keys.
        'tracking_id' => 'UA-1',
    ]);

    $settings = Settings::settings();

    expect($settings['site_name'])->toBe('Tagixo')
        ->and($settings['default_title'])->toBeNull()
        ->and($settings['custom_css'])->toBe('body { color: #111; }')
        ->and($settings)->not->toHaveKey('tracking_id');
});

it('creates a template that already claims something', function () {
    $page = PageResource::createRecord(['title' => 'About us']);

    $layout = LayoutResource::createRecord([
        'name' => 'Shop',
        'is_global' => false,
        'condition_all_pages' => false,
        'condition_homepage' => true,
        'condition_pages' => [$page->getKey()],
        'condition_models' => [],
        'condition_model_archives' => [],
    ]);

    expect($layout->name)->toBe('Shop')
        ->and($layout->conditions)->toBe([
            ['type' => 'homepage'],
            ['type' => 'page_id', 'value' => $page->getKey()],
        ])
        // And it lands where it can be dressed, not in a header nobody asked for.
        ->and(LayoutResource::afterCreateUrl($layout))->toContain('/layouts/'.$layout->getKey().'/edit');
});
