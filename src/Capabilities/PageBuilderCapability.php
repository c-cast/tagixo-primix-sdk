<?php

namespace Tagixo\Primix\Capabilities;

use Primix\Panel;
use Tagixo\PageBuilder\PageBuilder;
use Tagixo\Primix\Pages\SiteSettings;
use Tagixo\Primix\Pages\ThemeBuilder;
use Tagixo\Primix\Resources\MenuResource;
use Tagixo\Primix\TagixoPrimixPlugin;

/**
 * What the panel gains from the page builder beyond its record types: the Theme
 * Builder, where a template is dressed zone by zone, the menus of the site, and
 * the handful of settings the public site reads.
 *
 * The layouts themselves are a record type (`layouts`), so they are administered
 * like everything else; these are the screens no type could provide.
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
        if ($plugin->menusEnabled()) {
            $panel->resources([...$panel->getResources(), MenuResource::class]);
        }

        $pages = [];

        if ($plugin->themeBuilderEnabled()) {
            $pages[] = ThemeBuilder::class;
        }

        if ($plugin->siteSettingsEnabled()) {
            $pages[] = SiteSettings::class;
        }

        if ($pages !== []) {
            $panel->pages([...$panel->getPages(), ...$pages]);
        }
    }
}
