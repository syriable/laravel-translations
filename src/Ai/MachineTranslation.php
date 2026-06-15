<?php

namespace Syriable\Translations\Ai;

use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Enums\RevisionReason;
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

        return Message::withStamp(RevisionReason::Ai->value, $options['by'] ?? null, [], function () use ($message, $best, $result): Message {
            $message->fill([
                'value' => $best,
                'status' => MessageStatus::Draft,
                'ai_generated' => true,
                'ai_provider' => $result->provider,
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
            ->chunkById(config('translations.ai.batch_size', 20), function ($messages) use ($target, $options, &$count): void {
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
            ->whereHas('locale', fn ($query) => $query->where('is_source', true))
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
        return new TranslationRequest(
            text: $source,
            sourceLocale: optional(Locale::source())->code ?? config('translations.source_locale'),
            targetLocale: $target->code,
            tone: $options['tone'] ?? $target->tone,
            note: $phrase->note,
            usages: $this->usages($phrase),
            siblings: $this->siblings($phrase),
            glossary: ($options['glossary'] ?? true) ? $this->glossary->pairsFor($source, $target->id) : [],
            variants: $options['variants'] ?? config('translations.ai.variants', 1),
            provider: AiProviders::sanitize($options['provider'] ?? null),
            model: $options['model'] ?? null,
        );
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

        return (string) ($message?->value ?? '');
    }

    private function logUsage(Phrase $phrase, TranslationRequest $request, ?TranslationResult $result, ?string $error = null): void
    {
        AiUsage::query()->create([
            'provider' => $result?->provider ?? $request->provider ?? config('translations.ai.provider'),
            'model' => $result?->model ?? $request->model ?? config('translations.ai.model'),
            'phrase_id' => $phrase->id,
            'source_locale' => $request->sourceLocale,
            'target_locale' => $request->targetLocale,
            'input_chars' => $result?->inputChars ?? mb_strlen($request->text),
            'output_chars' => $result?->outputChars ?? 0,
            'cost' => $this->estimator->estimate(
                $result?->model ?? config('translations.ai.model'),
                $result?->inputChars ?? mb_strlen($request->text),
                $result?->outputChars ?? 0,
            ),
            'success' => $error === null,
            'error' => $error,
        ]);
    }
}
