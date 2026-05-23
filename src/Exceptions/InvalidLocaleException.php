<?php

declare(strict_types=1);

namespace Syriable\Translations\Exceptions;

final class InvalidLocaleException extends TranslationsException
{
    public static function empty(): self
    {
        return new self('A locale code may not be empty.');
    }
}
