<?php

declare(strict_types=1);

use Syriable\Translations\Tests\TestCase;

/*
| Feature tests boot a Testbench application via TestCase. Unit tests exercise
| the package's pure services directly and need no application.
*/

uses(TestCase::class)->in('Feature');

function fixturePath(string $path = ''): string
{
    return rtrim(__DIR__.'/Fixtures/'.ltrim($path, '/'), '/');
}
