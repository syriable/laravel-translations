<?php

namespace Syriable\Translations\Enums;

enum MemberRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Reviewer = 'reviewer';
    case Translator = 'translator';
    case Viewer = 'viewer';

    public function level(): int
    {
        return match ($this) {
            self::Owner => 100,
            self::Admin => 80,
            self::Reviewer => 60,
            self::Translator => 40,
            self::Viewer => 20,
        };
    }

    public function isAtLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    public function canTranslate(): bool
    {
        return $this->isAtLeast(self::Translator);
    }

    public function canReview(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Reviewer], true);
    }

    public function canManage(): bool
    {
        return $this->isAtLeast(self::Admin);
    }
}
