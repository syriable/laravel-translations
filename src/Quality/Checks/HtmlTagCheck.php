<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class HtmlTagCheck extends Check
{
    public function key(): string
    {
        return 'html_tag_mismatch';
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        $sourceTags = $this->scanner->htmlTags($source->value);

        if ($sourceTags === []) {
            return null;
        }

        sort($sourceTags);
        $targetTags = $this->scanner->htmlTags($message->value);
        sort($targetTags);

        if ($sourceTags === $targetTags) {
            return null;
        }

        return Issue::error(
            $this->key(),
            'HTML tags do not match the source string.',
            ['source' => $sourceTags, 'target' => $targetTags],
        );
    }
}
