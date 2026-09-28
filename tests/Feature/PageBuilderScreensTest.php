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

it('offers the conditions an editor can pick, and the targets they need', function () {
    $page = PageResource::createRecord(['title' => 'About us']);

    $fields = collect(LayoutResource::form(new Form)->getComponents())->keyBy->getName();

    expect($fields->keys()->all())->toBe(['name', 'is_global', 'conditions'])
        ->and(LayoutResource::conditionTypes())->toHaveKeys(['all_pages', 'homepage', 'page_id', 'model_all', 'model_archive'])
        ->and(LayoutResource::pageOptions())->toBe([$page->getKey() => 'About us']);
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
