<?php

namespace Syriable\Translations\Ai;

use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Support\TranslationRequest;
use Syriable\Translations\Support\TranslationResult;

class AiTranslator implements Translator
{
    public function translate(TranslationRequest $request): TranslationResult
    {
        $requested = AiProviders::sanitize($request->provider);
        $provider = $requested ?? config('translations.ai.provider', 'openai');
        $model = $request->model ?? config('translations.ai.model');

        $agent = new PhraseTranslationAgent($request);
        $response = $agent->prompt($request->text, provider: $provider, model: $model);

        $variants = collect($response['suggestions'] ?? [])
            ->map(fn (array $suggestion) => [
                'value' => $suggestion['value'],
                'confidence' => $suggestion['confidence'] ?? null,
                'recommended' => (bool) ($suggestion['recommended'] ?? false),
                'note' => $suggestion['note'] ?? null,
            ])
            ->all();

        $variants = $this->normalizeRecommended($variants);

        $outputChars = $variants === [] ? 0 : mb_strlen($variants[0]['value']);

        return new TranslationResult(
            variants: $variants,
            provider: $provider,
            model: $model,
            inputChars: mb_strlen($request->text),
            outputChars: $outputChars,
        );
    }

    /**
     * Ensure exactly one variant is flagged as recommended. The model's choice is
     * honoured when it marks a single suggestion; otherwise the highest-confidence
     * variant wins, falling back to the first one.
     *
     * @param  array<int, array{value: string, confidence: float|null, recommended: bool, note: string|null}>  $variants
     * @return array<int, array{value: string, confidence: float|null, recommended: bool, note: string|null}>
     */
    private function normalizeRecommended(array $variants): array
    {
        if ($variants === []) {
            return $variants;
        }

        $flagged = array_keys(array_filter($variants, fn (array $variant) => $variant['recommended']));

        $winner = count($flagged) === 1
            ? $flagged[0]
            : $this->highestConfidenceIndex($variants);

        foreach ($variants as $index => &$variant) {
            $variant['recommended'] = $index === $winner;
        }

        return $variants;
    }

    /**
     * @param  array<int, array{confidence: float|null}>  $variants
     */
    private function highestConfidenceIndex(array $variants): int
    {
        $winner = 0;

        foreach ($variants as $index => $variant) {
            if (($variant['confidence'] ?? 0) > ($variants[$winner]['confidence'] ?? 0)) {
                $winner = $index;
            }
        }

        return $winner;
    }
}
