<?php

declare(strict_types=1);

namespace Syriable\Translations\Tests\Support;

use Syriable\Translations\Contracts\TranslationDriver;
use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;

/**
 * A storage driver backed by an in-memory array. read() returns a detached
 * catalog (like real drivers) so callers can mutate it without side effects.
 */
final class InMemoryDriver implements TranslationDriver
{
    /**
     * @param  array<string, array<string, string|null>>  $store
     */
    public function __construct(private array $store = []) {}

    public function locales(): array
    {
        return array_keys($this->store);
    }

    public function read(Locale $locale): LocaleCatalog
    {
        return new LocaleCatalog($locale, $this->store[$locale->code] ?? []);
    }

    public function readAll(): Catalog
    {
        $catalog = new Catalog;

        foreach (array_keys($this->store) as $code) {
            $catalog->add($this->read(new Locale($code)));
        }

        return $catalog;
    }

    public function write(LocaleCatalog $catalog): void
    {
        $this->store[$catalog->locale->code] = $catalog->all();
    }
}
