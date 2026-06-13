<?php

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class LengthRatioCheck extends Check
{
    public function key(): string
    {
        return 'length_ratio';
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        $sourceLength = mb_strlen($source->value);

        if ($sourceLength < 10) {
            return null;
        }

        $ratio = mb_strlen($message->value) / $sourceLength;
        [$min, $max] = $this->bounds($message->locale->code);

        if ($ratio >= $min && $ratio <= $max) {
            return null;
        }

        return Issue::warning(
            $this->key(),
            sprintf('Translation length ratio %.2f is outside the expected range (%.2f–%.2f).', $ratio, $min, $max),
            ['ratio' => round($ratio, 2)],
        );
    }

    private function bounds(string $code): array
    {
        $config = config('translations.quality.length_ratio', []);
        $override = $config['overrides'][$code] ?? [];

        return [
            (float) ($override['min'] ?? $config['min'] ?? 0.5),
            (float) ($override['max'] ?? $config['max'] ?? 2.0),
        ];
    }
}
