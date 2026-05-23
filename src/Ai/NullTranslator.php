<?php

declare(strict_types=1);

namespace Syriable\Translations\Ai;

use Syriable\Translations\Contracts\Translator;

/**
 * The default translator: no provider is configured, so it translates nothing.
 * Apps bind their own {@see Translator} implementation to enable AI translation.
 */
final class NullTranslator implements Translator
{
    public function name(): string
    {
        return 'null';
    }

    public function model(): ?string
    {
        return null;
    }

    public function available(): bool
    {
        return false;
    }

    public function translate(array $strings, string $sourceLocale, string $targetLocale, array $glossary = []): array
    {
        return [];
    }
}
