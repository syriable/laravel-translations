<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain;

use Stringable;

final readonly class SourceReference implements Stringable
{
    public function __construct(
        public string $path,
        public int $line,
        public string $function,
    ) {}

    public function __toString(): string
    {
        return "{$this->path}:{$this->line}";
    }
}
