<?php

namespace Syriable\Translations\Enums;

enum RevisionReason: string
{
    case Manual = 'manual';
    case Import = 'import';
    case Ai = 'ai';
    case Rollback = 'rollback';
    case Bulk = 'bulk';
    case QualityFix = 'quality_fix';

    public function label(): string
    {
        return match ($this) {
            self::QualityFix => 'Quality fix',
            default => ucfirst($this->value),
        };
    }
}
