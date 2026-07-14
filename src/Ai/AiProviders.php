<?php

namespace Syriable\Translations\Ai;

use Illuminate\Support\Collection;
use Syriable\Translations\Enums\AiProvider;

class AiProviders
{
    public static function sanitize(?string $provider): ?string
    {
        if ($provider === null) {
            return null;
        }

        $allowed = config('translations.ai.allowed_providers', []);

        return in_array($provider, $allowed, true) ? $provider : null;
    }

    /**
     * The allowlisted providers that are actually usable, i.e. whose driver is
     * configured with credentials in the laravel/ai config (typically from a
     * key set in `.env`). Note this confirms a key is present, not that it is
     * valid or reachable.
     *
     * @return Collection<int, AiProvider>
     */
    public static function usable(): Collection
    {
        return collect(config('translations.ai.allowed_providers', []))
            ->filter(fn (string $name): bool => self::hasCredentials($name))
            ->map(fn (string $name): ?AiProvider => AiProvider::tryFrom($name))
            ->filter()
            ->values();
    }

    private static function hasCredentials(string $name): bool
    {
        $provider = config("ai.providers.{$name}");

        if (! is_array($provider)) {
            return false;
        }

        // Ollama runs locally and authenticates via its URL, not an API key.
        if ($name === AiProvider::Ollama->value) {
            return true;
        }

        return filled($provider['key'] ?? null);
    }
}
