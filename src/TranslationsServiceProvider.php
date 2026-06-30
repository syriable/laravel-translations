<?php

namespace Syriable\Translations;

use Illuminate\Support\ServiceProvider;
use Syriable\Translations\Ai\AiReviewer;
use Syriable\Translations\Ai\AiTranslator;
use Syriable\Translations\Commands\ExportCommand;
use Syriable\Translations\Commands\ImportCommand;
use Syriable\Translations\Commands\InstallCommand;
use Syriable\Translations\Commands\PruneRevisionsCommand;
use Syriable\Translations\Commands\ReviewCommand;
use Syriable\Translations\Commands\ScanLooseCommand;
use Syriable\Translations\Commands\ScanUsageCommand;
use Syriable\Translations\Commands\StatusCommand;
use Syriable\Translations\Commands\TranslateCommand;
use Syriable\Translations\Commands\ValidateCommand;
use Syriable\Translations\Contracts\Reviewer;
use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Events\ImportFinished;
use Syriable\Translations\Events\MessageSaved;
use Syriable\Translations\Listeners\FlushInsightsCache;
use Syriable\Translations\Listeners\RecordRevision;
use Syriable\Translations\Listeners\RunQualityChecks;
use Syriable\Translations\Listeners\ScanUsageAfterImport;

class TranslationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translations.php', 'translations');

        $this->app->bind(Translator::class, AiTranslator::class);
        $this->app->bind(Reviewer::class, AiReviewer::class);

        $this->app->singleton(TranslationManager::class);
    }

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerPublishing();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerListeners();
    }

    private function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'translations');
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/translations.php' => config_path('translations.php'),
        ], 'translations-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'translations-migrations');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/translations'),
        ], 'translations-lang');
    }

    private function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    private function registerCommands(): void
    {
        $this->commands([
            InstallCommand::class,
            ImportCommand::class,
            ExportCommand::class,
            StatusCommand::class,
            TranslateCommand::class,
            ValidateCommand::class,
            ReviewCommand::class,
            ScanUsageCommand::class,
            ScanLooseCommand::class,
            PruneRevisionsCommand::class,
        ]);
    }

    private function registerListeners(): void
    {
        $this->app['events']->listen(MessageSaved::class, RecordRevision::class);
        $this->app['events']->listen(MessageSaved::class, RunQualityChecks::class);
        $this->app['events']->listen(MessageSaved::class, FlushInsightsCache::class);
        $this->app['events']->listen(ImportFinished::class, ScanUsageAfterImport::class);
        $this->app['events']->listen(ImportFinished::class, FlushInsightsCache::class);
    }
}
