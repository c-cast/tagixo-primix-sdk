<?php

use Illuminate\Support\Facades\Route;
use Primix\PanelRegistry;
use Tagixo\Primix\Resources\LayoutResource;
use Tagixo\Primix\Resources\MailResource;
use Tagixo\Primix\Resources\PageResource;
use Tagixo\Primix\TagixoPrimixPlugin;

/*
 * Here the panel provider boots BEFORE the Tagixo packages, which is what
 * happens in a real application: a provider in bootstrap/providers.php comes
 * before the ones of the packages it uses.
 *
 * The plugin therefore decides its resources when it boots, not when it
 * registers: at registration the BuilderTypeRegistry can still be empty, and a
 * panel would show the capabilities' screens and not a single builder.
 */

it('gives the panel every installed builder, whatever order the providers booted in', function () {
    $panel = app(PanelRegistry::class)->get('admin');

    expect($panel->getResources())->toContain(PageResource::class, LayoutResource::class, MailResource::class)
        ->and(Route::has('primix.admin.pages.index'))->toBeTrue()
        ->and(Route::has('primix.admin.layouts.index'))->toBeTrue()
        ->and(Route::has('primix.admin.mails.index'))->toBeTrue();
});

it('still turns the record CRUD off, which has to happen before the core loads its routes', function () {
    expect(Tagixo\Core\Tagixo::managementApiEnabled())->toBeFalse()
        ->and(Route::has('tagixo.manage.index'))->toBeFalse()
        ->and(Route::has('tagixo.builder.embed'))->toBeTrue();
});

it('brings the capabilities along too', function () {
    expect(array_map(
        static fn ($capability): string => $capability->id(),
        app(TagixoPrimixPlugin::class)->resolveCapabilities(),
    ))->toContain('media-gallery', 'page-builder')
        ->and(Route::has('primix.admin.theme-builder'))->toBeTrue()
        ->and(Route::has('primix.admin.media.index'))->toBeTrue();
});
