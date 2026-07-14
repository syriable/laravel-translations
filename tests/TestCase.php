<?php

namespace Syriable\Translations\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Syriable\Metrics\MetricsServiceProvider;
use Syriable\Translations\TranslationsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            MetricsServiceProvider::class,
            TranslationsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('translations.lang_path', $this->langFixturePath());
        $app['config']->set('translations.source_locale', 'en');
    }

    protected function langFixturePath(): string
    {
        return sys_get_temp_dir().'/syriable-translations-tests/lang';
    }
}
