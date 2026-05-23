<?php

declare(strict_types=1);

namespace Syriable\Translations\Events;

/**
 * Dispatched after a bulk catalog import/transfer completes. Carries the locale
 * codes that were written so listeners (context scanning, validation, …) can
 * react to the whole batch at once.
 */
final readonly class TranslationsImported
{
    /**
     * @param  list<string>  $locales
     */
    public function __construct(
        public array $locales,
        public string $driver,
        public ?string $actor = null,
    ) {}
}
