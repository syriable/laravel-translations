<?php

namespace Syriable\Translations\Enums;

enum MessageStatus: string
{
    case Open = 'open';
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Untranslated',
            self::Draft => 'Translated',
            self::PendingReview => 'Pending review',
            self::Approved => 'Approved',
        };
    }

    public function isTranslated(): bool
    {
        return $this !== self::Open;
    }
}
