<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Enums\Severity;
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

        $problems = [];

        $edgesMatch = $this->leading($message->value) === $this->leading($source->value)
            && $this->trailing($message->value) === $this->trailing($source->value);

        if (! $edgesMatch) {
            $problems[] = __('translations::messages.quality.checks.whitespace.problems.leading_trailing');
        }

        // Repeated spaces inside the translation that the source does not have.
        if ($this->hasRepeatedSpaces($message->value) && ! $this->hasRepeatedSpaces($source->value)) {
            $problems[] = __('translations::messages.quality.checks.whitespace.problems.double_spaces');
        }

        if ($problems === []) {
            return null;
        }

        return new Issue(
            $this->key(),
            Severity::Warning,
            __('translations::messages.quality.checks.whitespace.description', ['problems' => implode(', ', $problems)]),
            __('translations::messages.quality.checks.whitespace.suggestion'),
            true,
            ['problems' => $problems],
        );
    }

    public function fix(Message $message, Message $source): ?string
    {
        $inner = trim($message->value);

        // Collapse runs of horizontal whitespace to a single space, unless the
        // source itself contains repeated spaces (then keep the translation as
        // the author wrote it).
        if (! $this->hasRepeatedSpaces($source->value)) {
            $inner = (string) preg_replace('/\h{2,}/u', ' ', $inner);
        }

        return $this->leading($source->value).$inner.$this->trailing($source->value);
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

    /**
     * Whether the value contains two or more consecutive horizontal whitespace
     * characters between non-space characters (i.e. internal "double spaces").
     */
    private function hasRepeatedSpaces(string $value): bool
    {
        return (bool) preg_match('/\S\h{2,}\S/u', $value);
    }
}
