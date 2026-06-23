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
        return $this->recommended()['value'] ?? $this->variants[0]['value'] ?? null;
    }

    /**
     * The explanatory note for the recommended (or first) variant, when available.
     */
    public function note(): ?string
    {
        return $this->recommended()['note'] ?? $this->variants[0]['note'] ?? null;
    }

    /**
     * The recommended variant, falling back to the first one.
     *
     * @return array{value: string, confidence: float|null, recommended?: bool, note: string|null}|null
     */
    public function recommended(): ?array
    {
        foreach ($this->variants as $variant) {
            if ($variant['recommended'] ?? false) {
                return $variant;
            }
        }

        return $this->variants[0] ?? null;
    }
}
