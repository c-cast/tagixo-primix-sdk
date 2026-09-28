<?php

namespace Tagixo\Primix\Tests\Support;

use Primix\Panel;
use Primix\PanelProvider;
use Tagixo\Primix\TagixoPrimixPlugin;

/**
 * The panel of the tests: nothing but the Tagixo plugin, so what shows up comes
 * from the builders installed and from nothing else.
 */
class TestPanelProvider extends PanelProvider
{
    /**
     * Configuration the test applies to the plugin before the panel takes it.
     *
     * @var \Closure(TagixoPrimixPlugin): TagixoPrimixPlugin|null
     */
    public static $configure = null;

    /**
     * Resources the panel of the application lists on its own.
     *
     * @var list<class-string<\Primix\Resources\Resource>>
     */
    public static array $panelResources = [];

    public function getId(): string
    {
        return 'admin';
    }

    public function panel(Panel $panel): Panel
    {
        $plugin = TagixoPrimixPlugin::make();

        if (static::$configure !== null) {
            $plugin = (static::$configure)($plugin);
        }

        return $panel
            ->path('admin')
            ->resources(static::$panelResources)
            ->plugin($plugin);
    }
}
