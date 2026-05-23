<?php

declare(strict_types=1);

namespace Syriable\Translations\Detection;

/**
 * A literal user-facing string found in source code that is not wrapped in a
 * translation helper — a candidate for extraction into a translation key.
 */
final readonly class DetectedString
{
    public function __construct(
        public string $text,
        public string $path,
        public int $line,
        public string $elementType,
        public string $scannerType,
    ) {}

    public function hash(): string
    {
        return sha1($this->text);
    }
}
