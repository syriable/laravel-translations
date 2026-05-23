<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain;

use Stringable;
use Syriable\Translations\Exceptions\InvalidLocaleException;

final readonly class Locale implements Stringable
{
    public function __construct(public string $code)
    {
        if (trim($code) === '') {
            throw InvalidLocaleException::empty();
        }
    }

    public static function of(string $code): self
    {
        return new self($code);
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
