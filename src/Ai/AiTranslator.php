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
        $response = $agent->prompt($request->text, ...array_filter(['provider' => $requested]));

        $variants = collect($response['suggestions'] ?? [])
            ->map(fn (array $suggestion) => [
                'value' => $suggestion['value'],
                'confidence' => $suggestion['confidence'] ?? null,
                'note' => $suggestion['note'] ?? null,
            ])
            ->all();

        $outputChars = $variants === [] ? 0 : mb_strlen($variants[0]['value']);

        return new TranslationResult(
            variants: $variants,
            provider: $provider,
            model: $model,
            inputChars: mb_strlen($request->text),
            outputChars: $outputChars,
        );
    }
}
