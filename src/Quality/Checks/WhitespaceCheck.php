<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class WhitespaceCheck extends Check
{
    public function key(): string
    {
        return 'whitespace';
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

        $sourceLeading = $this->leading($source->value);
        $sourceTrailing = $this->trailing($source->value);

        if ($this->leading($message->value) === $sourceLeading && $this->trailing($message->value) === $sourceTrailing) {
            return null;
        }

        return new Issue(
            $this->key(),
            \Syriable\Translations\Enums\Severity::Warning,
            'Leading or trailing whitespace does not match the source string.',
            'Trim the translation to match the source whitespace.',
            true,
        );
    }

    public function fix(Message $message, Message $source): ?string
    {
        return $this->leading($source->value).trim($message->value).$this->trailing($source->value);
    }

    private function leading(string $value): string
    {
        preg_match('/^\s*/', $value, $matches);

        return $matches[0];
    }

    private function trailing(string $value): string
    {
        preg_match('/\s*$/', $value, $matches);

        return $matches[0];
    }
}
