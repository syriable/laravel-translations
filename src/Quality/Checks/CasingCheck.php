<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Enums\Severity;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class CasingCheck extends Check
{
    public function key(): string
    {
        return 'casing';
    }

    public function fixable(): bool
    {
        return true;
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        if ($this->firstLetterUpper($source->value) === $this->firstLetterUpper($message->value)) {
            return null;
        }

        return new Issue(
            $this->key(),
            Severity::Info,
            'The first letter capitalization differs from the source string.',
            'Match the source capitalization.',
            true,
        );
    }

    public function fix(Message $message, Message $source): ?string
    {
        $value = $message->value;

        if ($value === '') {
            return $value;
        }

        $first = $this->firstLetterUpper($source->value)
            ? mb_strtoupper(mb_substr($value, 0, 1))
            : mb_strtolower(mb_substr($value, 0, 1));

        return $first.mb_substr($value, 1);
    }

    private function firstLetterUpper(string $value): ?bool
    {
        $first = mb_substr(ltrim($value), 0, 1);

        if ($first === '' || ! preg_match('/\p{L}/u', $first)) {
            return null;
        }

        return mb_strtoupper($first) === $first;
    }
}
