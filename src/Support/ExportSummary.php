<?php

namespace Syriable\Translations\Support;

class ExportSummary
{
    public function __construct(
        public int $localeCount = 0,
        public int $fileCount = 0,
        public int $phraseCount = 0,
        public int $durationMs = 0,
    ) {}

    public function toArray(): array
    {
        return [
            'locale_count' => $this->localeCount,
            'file_count' => $this->fileCount,
            'phrase_count' => $this->phraseCount,
            'duration_ms' => $this->durationMs,
        ];
    }
}
