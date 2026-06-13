<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class MissingPlaceholderCheck extends Check
{
    public function key(): string
    {
        return 'missing_placeholder';
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        $missing = array_diff(
            $this->scanner->placeholders($source->value),
            $this->scanner->placeholders($message->value),
        );

        if ($missing === []) {
            return null;
        }

        return Issue::error(
            $this->key(),
            'Translation is missing placeholders: '.implode(', ', $missing),
            ['missing' => array_values($missing)],
        );
    }
}
