<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Syriable\Translations\Tests\TestCase;

/*
| Feature tests boot a Testbench application via TestCase and run on a fresh
| in-memory database so metadata persistence works. Unit tests exercise the
| package's pure services directly and need no application.
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

function fixturePath(string $path = ''): string
{
    return rtrim(__DIR__.'/Fixtures/'.ltrim($path, '/'), '/');
}
