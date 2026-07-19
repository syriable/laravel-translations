<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class UrlEmailCheck extends Check
{
    public function key(): string
    {
        return 'url_email';
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        $expected = array_merge(
            $this->scanner->urls($source->value),
            $this->scanner->emails($source->value),
        );

        if ($expected === []) {
            return null;
        }

        $actual = array_merge(
            $this->scanner->urls($message->value),
            $this->scanner->emails($message->value),
        );

        $missing = array_diff($expected, $actual);

        if ($missing === []) {
            return null;
        }

        return Issue::error(
            $this->key(),
            __('translations::messages.quality.checks.url_email.description', [
                'missing' => implode(', ', $missing),
            ]),
            ['missing' => array_values($missing)],
        );
    }
}
