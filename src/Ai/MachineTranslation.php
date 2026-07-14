<?php

namespace Syriable\Translations\Ai;

use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Enums\RevisionReason;
use Syriable\Translations\Enums\Tone;
use Syriable\Translations\Glossary\Glossary;
use Syriable\Translations\Models\AiUsage;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Phrase;
use Syriable\Translations\Support\TranslationRequest;
use Syriable\Translations\Support\TranslationResult;
use Throwable;

class MachineTranslation
{
    private array $bundleKeys = [];

    public function __construct(
        private readonly Translator $translator,
        private readonly Glossary $glossary,
        private readonly CostEstimator $estimator,
    ) {}

    public function suggest(Phrase $phrase, Locale $target, array $options = []): TranslationResult
    {
        $source = $this->sourceText($phrase);
        $request = $this->request($phrase, $target, $source, $options);

        try {
            $result = $this->translator->translate($request);
        } catch (Throwable $exception) {
            $this->logUsage($phrase, $request, null, $exception->getMessage());

            throw $exception;
        }

        $this->logUsage($phrase, $request, $result);

        return $result;
    }

    public function apply(Phrase $phrase, Locale $target, array $options = []): ?Message
    {
        $result = $this->suggest($phrase, $target, $options);
        $best = $result->best();

        if ($best === null) {
            return null;
        }

        $message = Message::query()->firstOrNew([
            'phrase_id' => $phrase->id,
            'locale_id' => $target->id,
        ]);

        if ($message->exists && $message->value === $best) {
            return $message;
        }

        return Message::withStamp(RevisionReason::Ai->value, $options['by'] ?? null, [], function (?string $resolvedBy) use ($message, $best, $result): Message {
            $message->fill([
                'value' => $best,
                'status' => MessageStatus::Draft,
                'ai_generated' => true,
                'ai_provider' => $result->provider,
                'translated_by' => $resolvedBy ?? $message->translated_by,
            ])->save();

            return $message;
        });
    }

    public function translateOpen(Locale $target, array $options = []): int
    {
        $count = 0;

        Message::query()
            ->where('locale_id', $target->id)
            ->open()
            ->with('phrase.usages')
            ->chunkById(config('translations.ai.batch_size', 20), function (\Illuminate\Support\Collection $messages) use ($target, $options, &$count): void {
                foreach ($messages as $message) {
                    if ($this->apply($message->phrase, $target, $options)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function estimate(array $phraseIds, string $targetLocale): array
    {
        $texts = Message::query()
            ->whereIn('phrase_id', $phraseIds)
            ->whereHas('locale', fn (\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder => $query->where('is_source', true))
            ->pluck('value')
            ->filter()
            ->all();

        return [
            'phrase_count' => count($texts),
            'target_locale' => $targetLocale,
            'estimated_cost' => $this->estimator->estimateTexts(config('translations.ai.model'), $texts),
        ];
    }

    private function request(Phrase $phrase, Locale $target, string $source, array $options): TranslationRequest
    {
        $context = $options['context'] ?? config('translations.ai.context', true);

        return new TranslationRequest(
            text: $source,
            sourceLocale: optional(Locale::source())->code ?? config('translations.source_locale'),
            targetLocale: $target->code,
            tone: $this->toneInstruction($options['tone'] ?? $target->tone),
            note: $context ? $phrase->note : null,
            usages: $context ? $this->usages($phrase) : [],
            siblings: $context ? $this->siblings($phrase) : [],
            glossary: ($options['glossary'] ?? true) ? $this->glossary->pairsFor($source, $target->id) : [],
            variants: $options['variants'] ?? config('translations.ai.variants', 1),
            provider: AiProviders::sanitize($options['provider'] ?? null),
            model: $options['model'] ?? null,
        );
    }

    /**
     * Resolve the tone to a prompt instruction. Accepts a Tone enum, a backing value
     * (e.g. "formal"), or a free-form instruction string; returns null when absent.
     */
    private function toneInstruction(Tone|string|null $tone): ?string
    {
        if ($tone instanceof Tone) {
            return $tone->prompt();
        }

        if (is_string($tone) && ($resolved = Tone::tryFrom($tone)) !== null) {
            return $resolved->prompt();
        }

        return filled($tone) ? $tone : null;
    }

    private function usages(Phrase $phrase): array
    {
        $usages = $phrase->relationLoaded('usages')
            ? $phrase->usages
            : $phrase->usages()->limit(5)->get();

        return $usages->take(5)->pluck('file_path')->all();
    }

    private function siblings(Phrase $phrase): array
    {
        $keys = $this->bundleKeys[$phrase->bundle_id] ??= Phrase::query()
            ->where('bundle_id', $phrase->bundle_id)
            ->pluck('key', 'id')
            ->all();

        return collect($keys)->forget($phrase->id)->take(10)->values()->all();
    }

    private function sourceText(Phrase $phrase): string
    {
        $source = Locale::source();

        $message = $source
            ? Message::query()->where('phrase_id', $phrase->id)->where('locale_id', $source->id)->first()
            : null;

        return (string) ($message !== null ? $message->value : '');
    }

    private function logUsage(Phrase $phrase, TranslationRequest $request, ?TranslationResult $result, ?string $error = null): void
    {
        $inputChars = $result !== null ? $result->inputChars : mb_strlen($request->text);
        $outputChars = $result !== null ? $result->outputChars : 0;
        $model = $result !== null
            ? ($result->model ?? $request->model ?? config('translations.ai.model'))
            : ($request->model ?? config('translations.ai.model'));

        AiUsage::query()->create([
            'provider' => $result !== null
                ? $result->provider
                : ($request->provider ?? config('translations.ai.provider')),
            'model' => $model,
            'phrase_id' => $phrase->id,
            'source_locale' => $request->sourceLocale,
            'target_locale' => $request->targetLocale,
            'input_chars' => $inputChars,
            'output_chars' => $outputChars,
            'cost' => $this->estimator->estimate(
                $model,
                $inputChars,
                $outputChars,
            ),
            'success' => $error === null,
            'error' => $error,
        ]);
    }
}
