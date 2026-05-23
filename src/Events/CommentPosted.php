<?php

declare(strict_types=1);

namespace Syriable\Translations\Events;

/**
 * Dispatched when a comment is posted on a translation.
 */
final readonly class CommentPosted
{
    public function __construct(
        public string $locale,
        public string $key,
        public ?string $actor = null,
    ) {}
}
