<?php

declare(strict_types=1);

namespace Syriable\Translations;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Syriable\Translations\Analysis\HealthAnalyzer;
use Syriable\Translations\Console\Commands\CleanupRevisionsCommand;
use Syriable\Translations\Console\Commands\ExportCommand;
use Syriable\Translations\Console\Commands\ExtractCommand;
use Syriable\Translations\Console\Commands\HealthCommand;
use Syriable\Translations\Console\Commands\ImportCommand;
use Syriable\Translations\Console\Commands\LocalesCommand;
use Syriable\Translations\Console\Commands\SyncCommand;
use Syriable\Translations\Console\Commands\ValidateCommand;
use Syriable\Translations\Contracts\Scanner;
use Syriable\Translations\Contracts\ValidationRule;
use Syriable\Translations\Events\TranslationForgotten;
use Syriable\Translations\Events\TranslationSaved;
use Syriable\Translations\Events\TranslationsImported;
use Syriable\Translations\Extraction\AstKeyExtractor;
use Syriable\Translations\Extraction\Extractor;
use Syriable\Translations\Listeners\LogActivity;
use Syriable\Translations\Listeners\RecordRevision;
use Syriable\Translations\Management\CatalogManager;
use Syriable\Translations\Management\CatalogTransfer;
use Syriable\Translations\Storage\FormatRegistry;
use Syriable\Translations\Storage\Formats\JsonFormat;
use Syriable\Translations\Storage\Formats\PhpArrayFormat;
use Syriable\Translations\Storage\StorageManager;
use Syriable\Translations\Support\FileFinder;
use Syriable\Translations\Support\KeyRouter;
use Syriable\Translations\Validation\Rules\PluralFormRule;
use Syriable\Translations\Validation\ValidationPipeline;

final class TranslationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translations.php', 'translations');

        $this->app->singleton(AstKeyExtractor::class, fn (): AstKeyExtractor => new AstKeyExtractor($this->phpFunctions()));
        $this->app->singleton(FileFinder::class, fn (): FileFinder => new FileFinder);
        $this->app->singleton(KeyRouter::class, fn (): KeyRouter => new KeyRouter);
        $this->app->singleton(CatalogTransfer::class, fn (): CatalogTransfer => new CatalogTransfer);

        $this->app->singleton(FormatRegistry::class, function (): FormatRegistry {
            $flags = (int) config(
                'translations.storage.output.json_flags',
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );

            return new FormatRegistry([new PhpArrayFormat, new JsonFormat($flags)]);
        });

        $this->app->singleton(StorageManager::class, fn (Application $app): StorageManager => new StorageManager(
            $app->make(Filesystem::class),
            $app->make(FormatRegistry::class),
            $app->make(KeyRouter::class),
            (array) config('translations'),
        ));

        $this->app->singleton(CatalogManager::class, fn (Application $app): CatalogManager => new CatalogManager(
            $app->make(StorageManager::class),
            $app->make('events'),
        ));

        $this->app->singleton(Extractor::class, fn (Application $app): Extractor => new Extractor(
            $app->make(FileFinder::class),
            $this->resolveInstances((array) config('translations.extraction.scanners', []), Scanner::class),
            array_values((array) config('translations.extraction.exclude', [])),
            base_path(),
        ));

        $this->app->singleton(HealthAnalyzer::class, fn (): HealthAnalyzer => new HealthAnalyzer(
            array_values((array) config('translations.analysis.ignore', [])),
        ));

        $this->app->singleton(PluralFormRule::class, fn (): PluralFormRule => new PluralFormRule(
            $this->pluralCounts(),
        ));

        $this->app->singleton(ValidationPipeline::class, fn (Application $app): ValidationPipeline => new ValidationPipeline(
            $this->resolveInstances((array) config('translations.validation.rules', []), ValidationRule::class),
            (string) config('translations.locales.source', 'en'),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerMetadataListeners();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/translations.php' => config_path('translations.php'),
        ], 'translations-config');

        $this->commands([
            ExtractCommand::class,
            SyncCommand::class,
            ImportCommand::class,
            ExportCommand::class,
            ValidateCommand::class,
            HealthCommand::class,
            LocalesCommand::class,
            CleanupRevisionsCommand::class,
        ]);
    }

    /**
     * Wire the metadata listeners onto the write events. Skipped entirely when
     * metadata is disabled so the package runs in pure file mode.
     */
    private function registerMetadataListeners(): void
    {
        if (config('translations.metadata.enabled', true) !== true) {
            return;
        }

        $events = $this->app->make('events');

        $listeners = [
            TranslationSaved::class => [LogActivity::class, RecordRevision::class],
            TranslationForgotten::class => [LogActivity::class, RecordRevision::class],
            TranslationsImported::class => [LogActivity::class],
        ];

        foreach ($listeners as $event => $handlers) {
            foreach ($handlers as $handler) {
                $events->listen($event, $handler);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function phpFunctions(): array
    {
        $functions = array_values(array_filter(
            (array) config('translations.extraction.functions', ['__', 'trans', 'trans_choice']),
            static fn (string $function): bool => ! str_starts_with($function, '@'),
        ));

        return $functions !== [] ? $functions : ['__', 'trans', 'trans_choice'];
    }

    /**
     * Per-locale plural form counts that override the built-in defaults.
     *
     * @return array<string, int>
     */
    private function pluralCounts(): array
    {
        $counts = [];

        foreach ((array) config('translations.validation.plural.counts', []) as $locale => $count) {
            if (is_string($locale) && is_numeric($count)) {
                $counts[$locale] = (int) $count;
            }
        }

        return $counts;
    }

    /**
     * Resolve a list of class names from the container, keeping only those that
     * satisfy the given contract.
     *
     * @template T of object
     *
     * @param  array<array-key, mixed>  $classes
     * @param  class-string<T>  $contract
     * @return list<T>
     */
    private function resolveInstances(array $classes, string $contract): array
    {
        $instances = [];

        foreach ($classes as $class) {
            $instance = $this->app->make($class);

            if ($instance instanceof $contract) {
                $instances[] = $instance;
            }
        }

        return $instances;
    }
}
