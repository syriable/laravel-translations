<?php

declare(strict_types=1);

namespace Syriable\Translations\Events;

/**
 * Dispatched after a single translation value is written through the
 * {@see \Syriable\Translations\Management\CatalogManager}.
 */
final readonly class TranslationSaved
{
    public function __construct(
        public string $locale,
        public string $key,
        public ?string $previousValue,
        public ?string $value,
        public bool $created,
        public ?string $actor = null,
    ) {}
}
