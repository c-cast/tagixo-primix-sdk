<?php

use Illuminate\Support\Facades\Route;
use Tagixo\Primix\TagixoPrimixPlugin;
use Tagixo\Primix\Tests\Support\TestPanelProvider;

/*
 * No RefreshDatabase here: reconfiguring the plugin means refreshing the
 * application, and the in-memory database does not survive that.
 */

it('leaves the layouts out when the panel excludes that type', function () {
    // A layout is a record type now, so it is dropped like any other.
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->except(['layouts']);
    $this->refreshApplication();

    expect(Route::has('primix.admin.layouts.index'))->toBeFalse()
        ->and(Route::has('primix.admin.pages.index'))->toBeTrue()
        ->and(Route::has('primix.admin.site-settings'))->toBeTrue();
});

it('leaves the Theme Builder out when the panel says so', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->withThemeBuilder(false);
    $this->refreshApplication();

    expect(Route::has('primix.admin.theme-builder'))->toBeFalse()
        ->and(Route::has('primix.admin.layouts.index'))->toBeTrue();
});

it('leaves the site settings out when the panel says so', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->withSiteSettings(false);
    $this->refreshApplication();

    expect(Route::has('primix.admin.site-settings'))->toBeFalse()
        ->and(Route::has('primix.admin.layouts.index'))->toBeTrue();
});

it('leaves the menus out when the panel says so', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->withMenus(false);
    $this->refreshApplication();

    expect(Route::has('primix.admin.menus.index'))->toBeFalse()
        ->and(Route::has('primix.admin.layouts.index'))->toBeTrue();
});
