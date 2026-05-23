<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain;

/**
 * A single key/value pair within a locale.
 */
final readonly class Translation
{
    public function __construct(
        public string $key,
        public ?string $value,
    ) {}

    public function isMissing(): bool
    {
        return $this->value === null || $this->value === '';
    }

    public function isTranslated(): bool
    {
        return ! $this->isMissing();
    }
}
