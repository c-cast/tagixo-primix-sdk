<?php

use Illuminate\Support\Facades\Route;
use Tagixo\Primix\TagixoPrimixPlugin;
use Tagixo\Primix\Tests\Support\TestPanelProvider;

/*
 * No RefreshDatabase here on purpose: this checks what a panel option does to the
 * navigation, and the in-memory database does not survive the refresh that
 * reconfiguring the plugin needs.
 */

it('leaves that section out when the panel does not want it', function () {
    TestPanelProvider::$configure = fn (TagixoPrimixPlugin $plugin) => $plugin->withMediaGallery(false);
    $this->refreshApplication();

    expect(Route::has('primix.admin.media.index'))->toBeFalse()
        // The capability is still on: the picker belongs to the forms, not to the section.
        ->and(array_map(
            static fn ($capability): string => $capability->id(),
            app(TagixoPrimixPlugin::class)->resolveCapabilities(),
        ))->toContain('media-gallery');
});
