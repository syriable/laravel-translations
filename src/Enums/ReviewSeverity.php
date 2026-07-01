<?php

namespace Syriable\Translations\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * The priority the AI reviewer assigns to a translation issue. Unlike the
 * deterministic Severity (error/warning/info, which grades correctness), this
 * expresses how strongly the reviewer recommends acting on a linguistic issue.
 */
enum ReviewSeverity: string implements HasColor, HasIcon, HasLabel
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function order(): int
    {
        return match ($this) {
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }

    /**
     * Resolve the priority reported by the model, defaulting to Medium for any
     * value that isn't one of the three known levels.
     */
    public static function fromModel(mixed $value): self
    {
        return self::tryFrom(strtolower(trim((string) $value))) ?? self::Medium;
    }

    public function getLabel(): string
    {
        return __('translations::messages.enums.review_severity.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::High => 'danger',
            self::Medium => 'warning',
            self::Low => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::High => 'heroicon-o-exclamation-circle',
            self::Medium => 'heroicon-o-exclamation-triangle',
            self::Low => 'heroicon-o-information-circle',
        };
    }
}
