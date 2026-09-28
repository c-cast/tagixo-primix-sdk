<?php

namespace Tagixo\Primix\Capabilities;

use Primix\Panel;
use Tagixo\PageBuilder\PageBuilder;
use Tagixo\Primix\Pages\SiteSettings;
use Tagixo\Primix\Resources\LayoutResource;
use Tagixo\Primix\TagixoPrimixPlugin;

/**
 * What the panel gains from the page builder beyond its record types: the layouts
 * a page wears, and the handful of settings the public site reads.
 *
 * Neither is a builder type — a layout's content is edited from a page, and the
 * settings are values, not documents — so they get hand-written screens here.
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
        if ($plugin->layoutsEnabled()) {
            $panel->resources([...$panel->getResources(), LayoutResource::class]);
        }

        if ($plugin->siteSettingsEnabled()) {
            $panel->pages([...$panel->getPages(), SiteSettings::class]);
        }
    }
}
