<?php

declare(strict_types=1);

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Glossary\GlossaryEntry;

/**
 * Translates source strings into a target locale. Implementations wrap a
 * concrete provider (laravel/ai, Prism, a remote service, …); the package ships
 * a null implementation and apps bind their own.
 */
interface Translator
{
    /**
     * A stable provider identifier, recorded in usage logs (e.g. "openai").
     */
    public function name(): string;

    /**
     * The model used, if applicable.
     */
    public function model(): ?string;

    /**
     * Whether a usable provider is configured. When false the package will not
     * attempt translation.
     */
    public function available(): bool;

    /**
     * Translate a map of key => source text into the target locale. The glossary
     * entries provide agreed terminology the implementation should honour.
     * Returns a map of key => translated text for the keys it could translate.
     *
     * @param  array<string, string>  $strings
     * @param  list<GlossaryEntry>  $glossary
     * @return array<string, string>
     */
    public function translate(array $strings, string $sourceLocale, string $targetLocale, array $glossary = []): array;
}
