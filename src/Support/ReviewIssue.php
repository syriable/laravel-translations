<?php

namespace Syriable\Translations\Support;

use Syriable\Translations\Enums\Severity;

class ReviewIssue
{
    public function __construct(
        public readonly string $key,
        public readonly Severity $severity,
        public readonly string $description,
        public readonly ?string $suggestion = null,
    ) {}

    /**
     * @return array{key: string, severity: string, description: string, suggestion: string|null}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'severity' => $this->severity->value,
            'description' => $this->description,
            'suggestion' => $this->suggestion,
        ];
    }
}
