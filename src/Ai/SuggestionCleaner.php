<?php

namespace Syriable\Translations\Ai;

class SuggestionCleaner
{
    /**
     * Resolve the clean, copy/store-ready form of a model-produced translation.
     * Prefer the model's dedicated clean field; when it is missing, recover the
     * translation from the (possibly framed) proposed text by unwrapping
     * surrounding quotes or pulling the quoted translation out of an example
     * like `Translate to Arabic, for example: "…"`.
     */
    public static function plain(mixed $clean, string $proposed): string
    {
        $clean = self::unquote(trim((string) $clean));

        if ($clean !== '') {
            return $clean;
        }

        $unquoted = self::unquote($proposed);

        if ($unquoted !== $proposed) {
            return $unquoted;
        }

        return self::extractFramedTranslation($proposed) ?? $proposed;
    }

    /**
     * Strip a single matched pair of surrounding quotes (straight, curly,
     * single or guillemets) and trim the result.
     */
    public static function unquote(string $text): string
    {
        foreach ([['"', '"'], ['“', '”'], ["'", "'"], ['«', '»']] as [$open, $close]) {
            if (mb_strlen($text) >= mb_strlen($open) + mb_strlen($close)
                && str_starts_with($text, $open)
                && str_ends_with($text, $close)) {
                return trim(mb_substr($text, mb_strlen($open), mb_strlen($text) - mb_strlen($open) - mb_strlen($close)));
            }
        }

        return $text;
    }

    /**
     * Pull the translation out of a framed value such as
     * `Translate the text to Arabic, for example: "الترجمة."`. Only fires when a
     * quoted run follows a colon, so a translation that legitimately contains a
     * quoted phrase (e.g. `He said "hi"`) is left untouched.
     */
    private static function extractFramedTranslation(string $value): ?string
    {
        if (preg_match('/:\s*["“«\'](.+?)["”»\']/u', $value, $matches) !== 1) {
            return null;
        }

        $inner = trim($matches[1]);

        return $inner === '' ? null : $inner;
    }
}
