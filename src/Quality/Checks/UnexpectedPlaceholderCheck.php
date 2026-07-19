<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class UnexpectedPlaceholderCheck extends Check
{
    public function key(): string
    {
        return 'unexpected_placeholder';
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        $extra = array_diff(
            $this->scanner->placeholders($message->value),
            $this->scanner->placeholders($source->value),
        );

        if ($extra === []) {
            return null;
        }

        return Issue::warning(
            $this->key(),
            __('translations::messages.quality.checks.unexpected_placeholder.description', [
                'placeholders' => implode(', ', $extra),
            ]),
            ['extra' => array_values($extra)],
        );
    }
}
