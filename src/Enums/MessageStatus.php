<?php

namespace Syriable\Translations\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MessageStatus: string implements HasColor, HasIcon, HasLabel
{
    case Open = 'open';
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Untranslated',
            self::Draft => 'Translated',
            self::PendingReview => 'Pending review',
            self::Approved => 'Approved',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'gray',
            self::Draft => 'info',
            self::PendingReview => 'warning',
            self::Approved => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-o-language',
            self::Draft => 'heroicon-o-document-text',
            self::PendingReview => 'heroicon-o-clock',
            self::Approved => 'heroicon-o-check-circle',
        };
    }

    public function isTranslated(): bool
    {
        return $this !== self::Open;
    }
}
