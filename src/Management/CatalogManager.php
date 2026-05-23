<?php

declare(strict_types=1);

namespace Syriable\Translations\Management;

use Illuminate\Contracts\Events\Dispatcher;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;
use Syriable\Translations\Events\TranslationForgotten;
use Syriable\Translations\Events\TranslationSaved;
use Syriable\Translations\Events\TranslationsImported;
use Syriable\Translations\Storage\StorageManager;
use Syriable\Translations\Support\Actor;

/**
 * The single entry point for editing translation values. Reads the catalog
 * through the active storage driver, applies a change, persists it back to the
 * canonical lang files, and dispatches a domain event so collaboration features
 * (revisions, validation, activity, …) can react.
 *
 * Every mutation in the package — UI, API or command — should flow through
 * here so files never drift from the recorded metadata.
 */
final class CatalogManager
{
    public function __construct(
        private readonly StorageManager $storage,
        private readonly Dispatcher $events,
    ) {}

    public function read(string $locale, ?string $driver = null): LocaleCatalog
    {
        return $this->storage->driver($driver)->read(new Locale($locale));
    }

    public function get(string $locale, string $key, ?string $driver = null): ?string
    {
        return $this->read($locale, $driver)->get($key);
    }

    /**
     * Create or update a single translation value and persist it to storage.
     */
    public function set(string $locale, string $key, ?string $value, ?string $driver = null): void
    {
        $store = $this->storage->driver($driver);
        $catalog = $store->read(new Locale($locale));

        $created = ! $catalog->has($key);
        $previous = $catalog->get($key);

        if (! $created && $previous === $value) {
            return;
        }

        $catalog->put($key, $value);
        $store->write($catalog);

        $this->events->dispatch(new TranslationSaved(
            $locale,
            $key,
            $created ? null : $previous,
            $value,
            $created,
            Actor::current(),
        ));
    }

    /**
     * Remove a translation key from a locale and persist the change.
     */
    public function forget(string $locale, string $key, ?string $driver = null): void
    {
        $store = $this->storage->driver($driver);
        $catalog = $store->read(new Locale($locale));

        if (! $catalog->has($key)) {
            return;
        }

        $previous = $catalog->get($key);
        $catalog->forget($key);
        $store->write($catalog);

        $this->events->dispatch(new TranslationForgotten($locale, $key, $previous, Actor::current()));
    }

    /**
     * Announce that a batch of locales was imported/transferred, letting
     * batch-oriented listeners (context scanning, validation) react once.
     *
     * @param  list<string>  $locales
     */
    public function announceImported(array $locales, ?string $driver = null): void
    {
        $this->events->dispatch(new TranslationsImported(
            $locales,
            $driver ?? 'file',
            Actor::current(),
        ));
    }
}
