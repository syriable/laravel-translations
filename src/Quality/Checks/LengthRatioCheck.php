<?php

declare(strict_types=1);

namespace Syriable\Translations\Quality\Checks;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Quality\LengthRatio\LengthRatioEvaluator;
use Syriable\Translations\Support\Issue;

class LengthRatioCheck extends Check
{
    public function __construct(private readonly LengthRatioEvaluator $evaluator = new LengthRatioEvaluator)
    {
        parent::__construct();
    }

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

        $evaluation = $this->evaluator->evaluate(
            $source->value,
            $source->locale->code,
            $message->value,
            $message->locale->code,
        );

        $ratio = $evaluation['ratio'];
        $min = $evaluation['min'];
        $max = $evaluation['max'];

        if ($ratio >= $min && $ratio <= $max) {
            return null;
        }

        return Issue::warning(
            $this->key(),
            sprintf('Translation length ratio %.2f is outside the expected range (%.2f–%.2f).', $ratio, $min, $max),
            [
                'ratio' => round($ratio, 2),
                'source_density' => $evaluation['source_density'],
                'target_density' => $evaluation['target_density'],
            ],
        );
    }
}
