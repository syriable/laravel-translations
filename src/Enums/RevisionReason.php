<?php

namespace Syriable\Translations\Enums;

enum RevisionReason: string
{
    case Manual = 'manual';
    case Import = 'import';
    case Ai = 'ai';
    case Rollback = 'rollback';
    case Bulk = 'bulk';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
