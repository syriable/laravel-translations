<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain\Enums;

enum IssueSeverity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isError(): bool
    {
        return $this === self::Error;
    }
}
