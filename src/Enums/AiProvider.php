<?php

namespace Syriable\Translations\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasColor;

enum AiProvider: string implements HasLabel, HasIcon, HasColor
{
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';
    case Groq = 'groq';
    case Mistral = 'mistral';
    case XAI = 'xai';
    case DeepSeek = 'deepseek';
    case OpenRouter = 'openrouter';
    case Ollama = 'ollama';
    case Cohere = 'cohere';

    public function getLabel(): string
    {
        return match ($this) {
            self::OpenAI => 'OpenAI',
            self::Anthropic => 'Anthropic',
            self::Gemini => 'Google Gemini',
            self::Groq => 'Groq',
            self::Mistral => 'Mistral',
            self::XAI => 'xAI',
            self::DeepSeek => 'DeepSeek',
            self::OpenRouter => 'OpenRouter',
            self::Ollama => 'Ollama',
            self::Cohere => 'Cohere',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            default => 'gray',
        };
    }
    public function getIcon(): string
    {
        return match ($this) {
            self::OpenAI => 'flux-openai',
            self::Anthropic => 'flux-anthropic',
            self::Gemini => 'flux-google-gemini-color',
            self::Groq => 'flux-groq',
            self::Mistral => 'flux-mistral-color',
            self::XAI => 'flux-xai',
            self::DeepSeek => 'flux-deepseek',
            self::OpenRouter => 'flux-openrouter',
            self::Ollama => 'flux-ollama',
            self::Cohere => 'flux-cohere-color',
        };
    }
}
