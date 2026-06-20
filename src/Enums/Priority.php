<?php

namespace Syriable\Translations\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Priority: int implements HasColor, HasIcon, HasLabel
{
    case CRITICAL = 100;
    case HIGH = 75;
    case MEDIUM = 50;
    case LOW = 25;
    case OPTIONAL = 0;

    public function getLabel(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critical',
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
            self::OPTIONAL => 'Optional',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::CRITICAL => 'danger',
            self::HIGH => Color::Indigo,
            self::MEDIUM => Color::Sky,
            self::LOW => Color::Yellow,
            self::OPTIONAL => Color::Gray,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CRITICAL => 'heroicon-o-exclamation-triangle',
            self::HIGH => 'heroicon-o-arrow-up',
            self::MEDIUM => 'heroicon-o-minus',
            self::LOW => 'heroicon-o-arrow-down',
            self::OPTIONAL => 'heroicon-o-flag',
        };
    }
}
