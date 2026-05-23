<?php

declare(strict_types=1);

namespace Syriable\Translations\Tests\Support;

use Syriable\Translations\Contracts\Translator;

/**
 * A deterministic translator for tests: prefixes each value with the target
 * locale and applies any glossary terms it is given. Records the glossary it
 * received so tests can assert it was passed through.
 */
final class FakeTranslator implements Translator
{
    /**
     * @var list<\Syriable\Translations\Glossary\GlossaryEntry>
     */
    public array $receivedGlossary = [];

    public function name(): string
    {
        return 'fake';
    }

    public function model(): ?string
    {
        return 'fake-1';
    }

    public function available(): bool
    {
        return true;
    }

    public function translate(array $strings, string $sourceLocale, string $targetLocale, array $glossary = []): array
    {
        $this->receivedGlossary = $glossary;

        $translated = [];

        foreach ($strings as $key => $value) {
            foreach ($glossary as $entry) {
                $value = str_replace($entry->sourceTerm, $entry->translation, $value);
            }

            $translated[$key] = "[{$targetLocale}] {$value}";
        }

        return $translated;
    }
}
