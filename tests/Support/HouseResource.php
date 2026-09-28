<?php

namespace Tagixo\Primix\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Primix\Resources\Pages\ListRecords;
use Primix\Resources\Resource;

class House extends Model
{
    protected $table = 'tgx_test_houses';
}

/**
 * A resource of the application, which has nothing to do with Tagixo: the plugin
 * must not take the panel over.
 */
class HouseResource extends Resource
{
    protected static ?string $model = House::class;

    public static function getPages(): array
    {
        return ['index' => ListRecords::route('/')];
    }
}
