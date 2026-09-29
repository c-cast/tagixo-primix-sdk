<?php

use Illuminate\Support\Facades\Route;
use Primix\PanelRegistry;
use Tagixo\Core\BuilderTypeRegistry;
use Tagixo\Core\Tagixo;
use Tagixo\Primix\Resources\MailResource;
use Tagixo\Primix\Resources\PageResource;
use Tagixo\Primix\Resources\Pages\EditTagixoRecord;
use Tagixo\Primix\TagixoPrimixPlugin;
use Tagixo\Primix\Tests\Support\CustomPageResource;
use Tagixo\Primix\Tests\Support\HouseResource;
use Tagixo\Primix\Tests\Support\TestPanelProvider;

/*
 * What the panel offers comes from the BuilderTypeRegistry: the types of the
 * packages installed, and nothing for the builders that were not bought.
 */

it('gives an admin resource to every installed builder type, and only those', function () {
    $resources = app(TagixoPrimixPlugin::class)->resolveResources();

    // Layouts are a record type of the page builder too: a header and a footer
    // are documents the editor opens.
    expect(array_keys($resources))->toBe(['pages', 'popups', 'global-blocks', 'layouts', 'mails'])
        ->and($resources['pages'])->toBe(PageResource::class)
        ->and($resources['mails'])->toBe(MailResource::class);
});

it('registers those resources in the panel', function () {
    $panel = app(PanelRegistry::class)->get('admin');

    expect($panel->getResources())->toContain(PageResource::class, MailResource::class);
});

it('turns the record CRUD of the core off, and keeps the editor', function () {
    expect(Tagixo::managementApiEnabled())->toBeFalse()
        ->and(Route::has('tagixo.manage.index'))->toBeFalse()
        // The panel manages records; the editor is still the core's.
        ->and(Route::has('tagixo.builder.embed'))->toBeTrue()
        ->and(Route::has('tagixo.builder.data'))->toBeTrue()
        ->and(Route::has('tagixo.builder.save'))->toBeTrue();
});

it('administers only the types a panel asks for', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->only(['pages']);
    $this->refreshApplication();

    expect(array_keys(app(TagixoPrimixPlugin::class)->resolveResources()))->toBe(['pages']);
});

it('drops the types a panel excludes, records and builder untouched', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->except(['popups', 'global-blocks']);
    $this->refreshApplication();

    expect(array_keys(app(TagixoPrimixPlugin::class)->resolveResources()))->toBe(['pages', 'layouts', 'mails'])
        ->and(app(BuilderTypeRegistry::class)->has('popups'))->toBeTrue();
});

it('takes a resource class of the application for a type of its own', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin
        ->resource('pages', CustomPageResource::class);
    $this->refreshApplication();

    expect(app(TagixoPrimixPlugin::class)->resolveResources()['pages'])
        ->toBe(CustomPageResource::class);
});

it('groups and labels the navigation as the panel asked', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin
        ->navigationGroup('Content')
        ->icons(['pages' => 'pi pi-star']);
    $this->refreshApplication();

    expect(PageResource::getNavigationGroup())->toBe('Content')
        ->and(PageResource::getNavigationIcon())->toBe('pi pi-star')
        // Not configured: the icon of the type from the package config.
        ->and(MailResource::getNavigationIcon())->toBe('pi pi-envelope');
});

it('routes the listing, the create and the metadata screens of each type', function () {
    expect(Route::has('primix.admin.pages.index'))->toBeTrue()
        ->and(Route::has('primix.admin.pages.create'))->toBeTrue()
        ->and(Route::has('primix.admin.pages.edit'))->toBeTrue()
        ->and(Route::has('primix.admin.mails.index'))->toBeTrue()
        // Not installed here, so not administered.
        ->and(Route::has('primix.admin.sliders.index'))->toBeFalse();
});

it('shares one set of pages between the types, told apart by the route', function () {
    $edit = Route::getRoutes()->getByName('primix.admin.mails.edit');

    expect($edit->defaults['_livue_component'])->toBe(EditTagixoRecord::class)
        ->and($edit->defaults['_resource'])->toBe(MailResource::class)
        ->and(Route::getRoutes()->getByName('primix.admin.pages.edit')->defaults['_resource'])
        ->toBe(PageResource::class);
});

it('leaves the resources the panel already had alone', function () {
    TestPanelProvider::$panelResources = [HouseResource::class];
    $this->refreshApplication();

    $resources = app(PanelRegistry::class)->get('admin')->getResources();

    expect($resources)->toContain(HouseResource::class)
        ->and($resources)->toContain(PageResource::class);
});
