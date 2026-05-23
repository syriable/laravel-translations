<?php

declare(strict_types=1);

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;

/**
 * A storage driver reads and writes the translation catalog. The default
 * implementation works with lang files; custom drivers may target a database,
 * a remote service, or anything else.
 */
interface TranslationDriver
{
    /**
     * Discover the locale codes available in storage.
     *
     * @return list<string>
     */
    public function locales(): array;

    public function read(Locale $locale): LocaleCatalog;

    public function readAll(): Catalog;

    public function write(LocaleCatalog $catalog): void;
}
