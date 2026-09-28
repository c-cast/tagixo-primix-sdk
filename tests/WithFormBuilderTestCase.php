<?php

namespace Tagixo\Primix\Tests;

use Tagixo\FormBuilder\FormBuilderServiceProvider;
use Tagixo\Primix\TagixoPrimixServiceProvider;

/**
 * The same installation plus the form builder, booted with it from the start so
 * its migrations run too: what a customer who bought that builder as well has.
 */
abstract class WithFormBuilderTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        $providers = parent::getPackageProviders($app);
        $at = array_search(TagixoPrimixServiceProvider::class, $providers, true);

        array_splice($providers, $at === false ? count($providers) : $at, 0, [FormBuilderServiceProvider::class]);

        return $providers;
    }
}
