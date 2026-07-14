<?php

namespace Syriable\Translations\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Direction: string implements HasColor, HasIcon, HasLabel
{
    case Ltr = 'ltr';
    case Rtl = 'rtl';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ltr => 'Left to Right',
            self::Rtl => 'Right to Left',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ltr => 'success',
            self::Rtl => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Ltr => 'heroicon-m-arrow-small-right',
            self::Rtl => 'heroicon-m-arrow-small-left',
        };
    }
}
