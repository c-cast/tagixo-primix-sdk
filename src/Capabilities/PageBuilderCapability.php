<?php

namespace Tagixo\Primix\Capabilities;

use Primix\Panel;
use Tagixo\PageBuilder\PageBuilder;
use Tagixo\Primix\Pages\SiteSettings;
use Tagixo\Primix\Resources\LayoutResource;
use Tagixo\Primix\Resources\MenuResource;
use Tagixo\Primix\TagixoPrimixPlugin;

/**
 * What the panel gains from the page builder beyond its record types: the layouts
 * a page wears, the menus it carries, and the handful of settings the public site
 * reads.
 *
 * None of them is a builder type — a layout's content is edited from a page, a
 * menu is a list of links and the settings are values — so they get hand-written
 * screens here.
 */
class PageBuilderCapability implements Capability
{
    public function id(): string
    {
        return 'page-builder';
    }

    public function available(): bool
    {
        return app()->bound(PageBuilder::class);
    }

    public function apply(Panel $panel, TagixoPrimixPlugin $plugin): void
    {
        $resources = [];

        if ($plugin->layoutsEnabled()) {
            $resources[] = LayoutResource::class;
        }

        if ($plugin->menusEnabled()) {
            $resources[] = MenuResource::class;
        }

        if ($resources !== []) {
            $panel->resources([...$panel->getResources(), ...$resources]);
        }

        if ($plugin->siteSettingsEnabled()) {
            $panel->pages([...$panel->getPages(), SiteSettings::class]);
        }
    }
}
