<?php

use Tagixo\Primix\Tests\AppFirstTestCase;
use Tagixo\Primix\Tests\TestCase;
use Tagixo\Primix\Tests\WithDocumentBuilderTestCase;
use Tagixo\Primix\Tests\WithFormBuilderTestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// The same installation with the form builder as well, booted with it from the
// start so its migrations run too.
uses(WithFormBuilderTestCase::class)->in('WithFormBuilder');

uses(WithDocumentBuilderTestCase::class)->in('WithDocumentBuilder');

// An application's own providers boot before its packages': the panel provider
// comes first here, as it does in bootstrap/providers.php.
uses(AppFirstTestCase::class)->in('AppFirst');
