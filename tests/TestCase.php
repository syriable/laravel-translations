<?php

declare(strict_types=1);

namespace Syriable\Translations\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Syriable\Translations\TranslationsServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            TranslationsServiceProvider::class,
        ];
    }
}
