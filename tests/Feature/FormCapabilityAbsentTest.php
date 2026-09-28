<?php

use Tagixo\Primix\Capabilities\FormBuilderCapability;
use Tagixo\Primix\TagixoPrimixPlugin;

/*
 * The default installation of these tests has no form builder, and that is what
 * this file is for: a capability of a package that is not there must leave no
 * trace at all.
 */

it('stays away when the form builder is not installed', function () {
    expect(app(FormBuilderCapability::class)->available())->toBeFalse()
        ->and(app(TagixoPrimixPlugin::class)->resolveCapabilities())->toBe([])
        // No resource either: `forms` is not a registered type here.
        ->and(app(TagixoPrimixPlugin::class)->resolveResources())->not->toHaveKey('forms');
});
