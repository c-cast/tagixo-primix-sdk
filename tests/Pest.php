<?php

use Tagixo\Primix\Tests\TestCase;
use Tagixo\Primix\Tests\WithDocumentBuilderTestCase;
use Tagixo\Primix\Tests\WithFormBuilderTestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// The same installation with the form builder as well, booted with it from the
// start so its migrations run too.
uses(WithFormBuilderTestCase::class)->in('WithFormBuilder');

uses(WithDocumentBuilderTestCase::class)->in('WithDocumentBuilder');
