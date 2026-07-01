<?php

namespace Syriable\Translations\Ai;

use Closure;
use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Support\TranslationRequest;
use Syriable\Translations\Support\TranslationResult;

class FakeTranslator implements Translator
{
    /** @var array<int, TranslationRequest> */
    public array $requests = [];

    public function __construct(
        private readonly ?Closure $using = null,
    ) {}

    public function translate(TranslationRequest $request): TranslationResult
    {
        $this->requests[] = $request;

        $value = $this->using
            ? ($this->using)($request)
            : "[{$request->targetLocale}] {$request->text}";

        $variants = [];

        for ($i = 0; $i < max(1, $request->variants); $i++) {
            $variantValue = $i === 0 ? $value : "{$value} ({$i})";

            $variants[] = [
                'value' => $variantValue,
                'base_value' => $variantValue,
                'confidence' => 0.9 - ($i * 0.1),
                'recommended' => $i === 0,
                'note' => $i === 0 ? "Fake explanation in {$request->sourceLocale}." : null,
            ];
        }

        return new TranslationResult(
            variants: $variants,
            provider: $request->provider ?? 'fake',
            model: $request->model ?? 'fake',
            inputChars: mb_strlen($request->text),
            outputChars: mb_strlen($value),
        );
    }
}
