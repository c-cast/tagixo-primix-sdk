<?php

namespace Tagixo\Primix\Tests;

use Tagixo\DocumentBuilder\DocumentBuilderServiceProvider;
use Tagixo\Primix\TagixoPrimixServiceProvider;

/**
 * The same installation plus the document builder, booted with it from the start
 * so its migrations run too.
 */
abstract class WithDocumentBuilderTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        $providers = parent::getPackageProviders($app);
        $at = array_search(TagixoPrimixServiceProvider::class, $providers, true);

        array_splice($providers, $at === false ? count($providers) : $at, 0, [DocumentBuilderServiceProvider::class]);

        return $providers;
    }
}
