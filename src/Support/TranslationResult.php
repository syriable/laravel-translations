<?php

namespace Syriable\Translations\Support;

class TranslationResult
{
    public function __construct(
        public readonly array $variants,
        public readonly string $provider,
        public readonly ?string $model = null,
        public readonly int $inputChars = 0,
        public readonly int $outputChars = 0,
    ) {}

    public function best(): ?string
    {
        return $this->variants[0]['value'] ?? null;
    }
}
