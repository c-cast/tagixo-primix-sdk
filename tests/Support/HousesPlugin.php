<?php

namespace Tagixo\Primix\Tests\Support;

use Primix\Contracts\Plugin;
use Primix\Panel;

/**
 * The admin screens a package brings with its content type — the shape
 * `ccast/tagixo-articles` has for its articles, categories and tags.
 */
class HousesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tagixo/houses-panel';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $panel->resources([...$panel->getResources(), HouseResource::class]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
