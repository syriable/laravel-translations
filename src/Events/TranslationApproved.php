<?php

declare(strict_types=1);

namespace Syriable\Translations\Events;

/**
 * Dispatched when a translation is approved in the review workflow.
 */
final readonly class TranslationApproved
{
    public function __construct(
        public string $locale,
        public string $key,
        public ?string $actor = null,
    ) {}
}
