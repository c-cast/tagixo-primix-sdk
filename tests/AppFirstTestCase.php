<?php

namespace Tagixo\Primix\Tests;

use Tagixo\Primix\Tests\Support\TestPanelProvider;

/**
 * The order a real application boots in: its own providers first, the packages
 * after. A panel provider listed in `bootstrap/providers.php` boots before the
 * Tagixo ones, so anything the plugin decides too early sees an empty registry.
 */
abstract class AppFirstTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        $providers = parent::getPackageProviders($app);

        return [
            TestPanelProvider::class,
            ...array_values(array_filter(
                $providers,
                static fn (string $provider): bool => $provider !== TestPanelProvider::class,
            )),
        ];
    }
}
