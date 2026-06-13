<?php

namespace Syriable\Translations\Scanning\Loose;

class FalsePositiveFilter
{
    public function rejects(string $text): bool
    {
        $text = trim($text);

        if ($text === '') {
            return true;
        }

        if (mb_strlen($text) < config('translations.scanning.loose.min_length', 5)) {
            return true;
        }

        if (str_word_count($text) < config('translations.scanning.loose.min_words', 2)) {
            return true;
        }

        if (preg_match('/^[\d\s\p{P}\p{S}]+$/u', $text)) {
            return true;
        }

        if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $text) || str_contains($text, '::')) {
            return true;
        }

        if (preg_match('/^(https?:\/\/|\/|#|\{|@|\$)/', $text)) {
            return true;
        }

        if (! preg_match('/\p{L}{2,}/u', $text)) {
            return true;
        }

        return false;
    }
}
