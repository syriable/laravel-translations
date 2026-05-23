<?php

declare(strict_types=1);

namespace Syriable\Translations\Events;

/**
 * Dispatched after a translation key is removed from a locale through the
 * {@see \Syriable\Translations\Management\CatalogManager}.
 */
final readonly class TranslationForgotten
{
    public function __construct(
        public string $locale,
        public string $key,
        public ?string $previousValue,
        public ?string $actor = null,
    ) {}
}
