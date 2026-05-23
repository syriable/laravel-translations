<?php

declare(strict_types=1);

namespace Syriable\Translations\Events;

/**
 * Dispatched when a translation is rejected in the review workflow.
 */
final readonly class TranslationRejected
{
    public function __construct(
        public string $locale,
        public string $key,
        public ?string $feedback = null,
        public ?string $actor = null,
    ) {}
}
