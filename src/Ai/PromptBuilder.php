<?php

namespace Syriable\Translations\Ai;

use Syriable\Translations\Support\TranslationRequest;

class PromptBuilder
{
    private const MAX_LENGTH = 16000;

    public function build(TranslationRequest $request): string
    {
        $lines = [
            "You are a professional translator translating from {$request->sourceLocale} to {$request->targetLocale}.",
        ];

        if ($request->tone) {
            $lines[] = "Use a {$request->tone} tone.";
        }

        if ($request->note) {
            $lines[] = "Developer note about this string: {$request->note}";
        }

        if ($request->usages !== []) {
            $lines[] = 'It appears in: '.implode('; ', array_slice($request->usages, 0, 5)).'.';
        }

        if ($request->siblings !== []) {
            $lines[] = 'Related strings in the same file: '.implode(', ', array_slice($request->siblings, 0, 10)).'.';
        }

        if ($request->glossary !== []) {
            $pairs = [];

            foreach ($request->glossary as $term => $translation) {
                $pairs[] = "\"{$term}\" must be translated as \"{$translation}\"";
            }

            $lines[] = 'Glossary: '.implode('; ', $pairs).'.';
        }

        $lines[] = 'Rules: preserve placeholders like :name and {count}; preserve HTML tags; keep URLs and emails unchanged; match the source capitalization and surrounding whitespace.';
        $lines[] = "Translate the following text:\n\n{$request->text}";

        return mb_substr(implode("\n", $lines), 0, self::MAX_LENGTH);
    }
}
