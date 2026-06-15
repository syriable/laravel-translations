<?php

namespace Syriable\Translations\Ai;

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
}
