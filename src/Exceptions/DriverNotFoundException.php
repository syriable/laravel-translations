<?php

declare(strict_types=1);

namespace Syriable\Translations\Exceptions;

final class DriverNotFoundException extends TranslationsException
{
    public static function named(string $driver): self
    {
        return new self("Translation storage driver [{$driver}] is not configured.");
    }
}
