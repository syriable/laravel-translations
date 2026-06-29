<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class InconsistentPluralSelectorCheck extends Check
{
    public function key(): string
    {
        return 'inconsistent_plural_selector';
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        $missing = $this->scanner->missingPluralSelectors($source->value);

        if ($missing === []) {
            return null;
        }

        return Issue::warning(
            $this->key(),
            'The source plural string is missing explicit selectors (e.g. {0}, [1,19], [20,*]) on segment(s): '.implode(', ', $missing).'.',
            ['missing_segments' => $missing],
        );
    }
}
