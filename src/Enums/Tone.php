<?php

namespace Syriable\Translations\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Tone: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Neutral = 'neutral';
    case Formal = 'formal';
    case Informal = 'informal';
    case Friendly = 'friendly';
    case Professional = 'professional';

    public function getLabel(): string
    {
        return match ($this) {
            self::Neutral => 'Neutral',
            self::Formal => 'Formal',
            self::Informal => 'Informal',
            self::Friendly => 'Friendly',
            self::Professional => 'Professional',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Neutral => 'Balanced, standard tone suitable for most content.',
            self::Formal => 'Polite, formal register that avoids contractions and uses honorifics where appropriate.',
            self::Informal => 'Casual, conversational tone using everyday language.',
            self::Friendly => 'Warm and approachable, as if speaking to a friend.',
            self::Professional => 'Clear, concise and business-appropriate tone for workplace content.',
        };
    }

    public function getColor(): string|array
    {
        return match ($this) {
            self::Neutral => Color::Gray,
            self::Formal => Color::Indigo,
            self::Informal => Color::Amber,
            self::Friendly => Color::Emerald,
            self::Professional => Color::Sky,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Neutral => 'heroicon-o-scale',
            self::Formal => 'heroicon-o-building-library',
            self::Informal => 'heroicon-o-chat-bubble-left-right',
            self::Friendly => 'heroicon-o-face-smile',
            self::Professional => 'heroicon-o-briefcase',
        };
    }

    /**
     * A short instruction describing the tone, intended to be injected into an AI
     * translation prompt so generated translations match the locale's desired register.
     */
    public function prompt(): string
    {
        return match ($this) {
            self::Neutral => 'Use a balanced, neutral tone.',
            self::Formal => 'Use a formal, polite tone; avoid contractions and slang.',
            self::Informal => 'Use an informal, conversational tone with everyday language.',
            self::Friendly => 'Use a warm, friendly and approachable tone.',
            self::Professional => 'Use a professional, business-appropriate tone.',
        };
    }
}
