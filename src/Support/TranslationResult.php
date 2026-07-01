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

    /**
     * The clean, copy/store-ready translation for the recommended (or first)
     * variant — the exact string to write to a language file, without any
     * framing the model may have added. Falls back to the proposed value.
     */
    public function best(): ?string
    {
        $recommended = $this->recommended() ?? [];

        return $recommended['base_value'] ?? $recommended['value'] ?? null;
    }

    /**
     * The translation exactly as the model proposed it, which may include
     * surrounding framing (quotes, an "e.g. …" example, etc.). Useful for
     * display; use best() for the value to store or copy.
     */
    public function proposed(): ?string
    {
        return ($this->recommended() ?? [])['value'] ?? null;
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
     * @return array{value: string, base_value?: string, confidence: float|null, recommended?: bool, note: string|null}|null
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
