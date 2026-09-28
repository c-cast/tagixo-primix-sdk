<?php

namespace Tagixo\Primix\Resources\Pages;

use Primix\Resources\Pages\CreateRecord;
use Tagixo\Primix\Resources\MenuResource;

/**
 * A menu is created empty: its items are added once it exists, which is why the
 * item repeater is hidden here.
 */
class CreateMenu extends CreateRecord
{
    protected static ?string $resource = MenuResource::class;
}
