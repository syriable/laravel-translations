<?php

namespace Syriable\Translations\Support;

class ImportSummary
{
    public function __construct(
        public int $localeCount = 0,
        public int $phraseCount = 0,
        public int $createdCount = 0,
        public int $updatedCount = 0,
        public int $durationMs = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'locale_count' => $this->localeCount,
            'phrase_count' => $this->phraseCount,
            'created_count' => $this->createdCount,
            'updated_count' => $this->updatedCount,
            'duration_ms' => $this->durationMs,
        ];
    }
}
