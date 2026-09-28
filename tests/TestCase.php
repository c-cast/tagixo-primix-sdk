<?php

namespace Tagixo\Primix\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use LiVue\LiVueServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Primix\Actions\PrimixActionsServiceProvider;
use Primix\Details\PrimixDetailsServiceProvider;
use Primix\Forms\PrimixFormsServiceProvider;
use Primix\Notifications\PrimixNotificationsServiceProvider;
use Primix\PrimixServiceProvider;
use Primix\Support\PrimixSupportServiceProvider;
use Primix\Tables\PrimixTablesServiceProvider;
use Primix\Widgets\PrimixWidgetsServiceProvider;
use Tagixo\Core\TagixoServiceProvider;
use Tagixo\MailBuilder\MailBuilderServiceProvider;
use Tagixo\PageBuilder\PageBuilderServiceProvider;
use Tagixo\Primix\TagixoPrimixServiceProvider;
use Tagixo\Primix\Tests\Support\TestPanelProvider;

/**
 * Core + page builder + mail builder + the SDK, in a panel that registers the
 * plugin. Two builders only, on purpose: the tests have to show that the panel
 * offers the types of the packages installed and not a line more.
 */
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            LiVueServiceProvider::class,
            PrimixSupportServiceProvider::class,
            PrimixActionsServiceProvider::class,
            PrimixFormsServiceProvider::class,
            PrimixTablesServiceProvider::class,
            PrimixNotificationsServiceProvider::class,
            PrimixDetailsServiceProvider::class,
            PrimixWidgetsServiceProvider::class,
            PrimixServiceProvider::class,
            TagixoServiceProvider::class,
            PageBuilderServiceProvider::class,
            MailBuilderServiceProvider::class,
            TagixoPrimixServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * The users table of Laravel too: the panel screens are behind its auth.
     * After the refresh, or RefreshDatabase would wipe it again.
     */
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed(): void
    {
        $this->loadLaravelMigrations();
    }

    protected function tearDown(): void
    {
        TestPanelProvider::$configure = null;

        parent::tearDown();
    }
}
