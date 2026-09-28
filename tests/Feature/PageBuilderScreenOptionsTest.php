<?php

use Illuminate\Support\Facades\Route;
use Tagixo\Primix\TagixoPrimixPlugin;
use Tagixo\Primix\Tests\Support\TestPanelProvider;

/*
 * No RefreshDatabase here: reconfiguring the plugin means refreshing the
 * application, and the in-memory database does not survive that.
 */

it('leaves the layouts out when the panel says so', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->withLayouts(false);
    $this->refreshApplication();

    expect(Route::has('primix.admin.layouts.index'))->toBeFalse()
        // The pages are still administered: only that screen is gone.
        ->and(Route::has('primix.admin.pages.index'))->toBeTrue()
        ->and(Route::has('primix.admin.site-settings'))->toBeTrue();
});

it('leaves the site settings out when the panel says so', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->withSiteSettings(false);
    $this->refreshApplication();

    expect(Route::has('primix.admin.site-settings'))->toBeFalse()
        ->and(Route::has('primix.admin.layouts.index'))->toBeTrue();
});
