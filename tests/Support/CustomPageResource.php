<?php

namespace Tagixo\Primix\Tests\Support;

use Tagixo\Primix\Resources\TagixoRecordResource;

/**
 * What an application writes when it wants its own admin screens for a type.
 */
class CustomPageResource extends TagixoRecordResource
{
    protected static string $tagixoType = 'pages';
}
