<?php

namespace Syriable\Translations\Support;

class ReviewRequest
{
    /**
     * @param  array<string, array{source: string, target: string}>  $pairs  Translations to review, keyed by dotted key.
     */
    public function __construct(
        public readonly array $pairs,
        public readonly string $sourceLocale,
        public readonly string $targetLocale,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
    ) {}
}
