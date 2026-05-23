<?php

declare(strict_types=1);

namespace Syriable\Translations\Analysis;

/**
 * Completeness of a single locale measured against the source locale's keys.
 */
final readonly class CompletenessReport
{
    /**
     * @param  list<string>  $missingKeys
     */
    public function __construct(
        public string $locale,
        public int $total,
        public int $translated,
        public array $missingKeys,
    ) {}

    public function percentage(): float
    {
        if ($this->total === 0) {
            return 100.0;
        }

        return round($this->translated / $this->total * 100, 1);
    }

    public function isComplete(): bool
    {
        return $this->missingKeys === [];
    }
}
