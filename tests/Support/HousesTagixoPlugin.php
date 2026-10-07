<?php

namespace Tagixo\Primix\Tests\Support;

use Tagixo\Core\Contracts\HasPlugin;
use Tagixo\Core\Tagixo;
use Tagixo\Core\TagixoPluginBase;

/**
 * A Tagixo plugin that registers a content type and, implementing HasPlugin,
 * also says which panel plugin dresses it.
 */
class HousesTagixoPlugin extends TagixoPluginBase implements HasPlugin
{
    public function getId(): string
    {
        return 'tagixo/houses';
    }

    public function boot(Tagixo $tagixo): void
    {
        //
    }

    public function getPlugin(): object
    {
        return HousesPlugin::make();
    }
}
