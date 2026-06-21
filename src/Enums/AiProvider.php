<?php

namespace Syriable\Translations\Enums;

use Filament\Support\Contracts\HasLabel;

enum AiProvider: string implements HasLabel
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
}
