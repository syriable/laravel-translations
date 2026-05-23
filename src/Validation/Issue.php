<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation;

use Syriable\Translations\Domain\Enums\IssueSeverity;

/**
 * A single problem found while validating a translation against its source.
 */
final readonly class Issue
{
    public function __construct(
        public string $rule,
        public IssueSeverity $severity,
        public string $key,
        public string $locale,
        public string $message,
        public ?string $suggestion = null,
    ) {}
}
