<?php

namespace Syriable\Translations\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Severity: string implements HasColor, HasIcon, HasLabel
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';

    public function order(): int
    {
        return match ($this) {
            self::Error => 3,
            self::Warning => 2,
            self::Info => 1,
        };
    }

    public function getLabel(): string
    {
        return __('translations::messages.enums.severity.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Error => 'danger',
            self::Warning => 'warning',
            self::Info => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Error => 'heroicon-o-x-circle',
            self::Warning => 'heroicon-o-exclamation-triangle',
            self::Info => 'heroicon-o-information-circle',
        };
    }
}
