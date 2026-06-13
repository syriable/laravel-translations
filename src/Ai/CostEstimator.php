<?php

namespace Syriable\Translations\Ai;

class CostEstimator
{
    public function estimate(string $model, int $inputChars, int $outputChars = 0): float
    {
        $rates = config("translations.ai.cost_rates.{$model}", ['input' => 0.5, 'output' => 0.5]);

        $cost = ($inputChars / 1_000_000) * (float) $rates['input']
            + ($outputChars / 1_000_000) * (float) $rates['output'];

        return round($cost, 6);
    }

    public function estimateTexts(string $model, array $texts, float $expansion = 1.2): float
    {
        $inputChars = array_sum(array_map('mb_strlen', $texts));

        return $this->estimate($model, $inputChars, (int) round($inputChars * $expansion));
    }
}
