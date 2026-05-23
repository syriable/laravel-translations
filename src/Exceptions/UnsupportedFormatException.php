<?php

declare(strict_types=1);

namespace Syriable\Translations\Exceptions;

final class UnsupportedFormatException extends TranslationsException
{
    public static function forExtension(string $extension): self
    {
        return new self("No translation file format is registered for extension [{$extension}].");
    }
}
