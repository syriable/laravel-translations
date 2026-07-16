<?php

namespace Syriable\Translations\Ai;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Syriable\Translations\Contracts\Reviewer;
use Syriable\Translations\Models\AiUsage;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Support\ReviewRequest;
use Syriable\Translations\Support\ReviewResult;
use Throwable;

class MachineReview
{
    public function __construct(
        private readonly Reviewer $reviewer,
        private readonly CostEstimator $estimator,
    ) {}

    /**
     * Review the translated messages of a locale for quality issues.
     *
     * Options:
     *   - `phrase_ids` (array<int>) limit the review to specific phrases
     *   - `provider`   (string)     request a specific allowlisted provider
     *   - `model`      (string)     request a specific model
     *
     * The pairs are reviewed in batches (config `translations.ai.review.batch_size`)
     * and the per-batch results are folded into a single result.
     */
    public function review(Locale $target, array $options = []): ReviewResult
    {
        $sourceLocale = optional(Locale::source())->code ?? config('translations.source_locale');
        $provider = AiProviders::sanitize($options['provider'] ?? null);
        $model = $options['model'] ?? null;

        $pairs = $this->pairs($target, $options['phrase_ids'] ?? null);

        $result = ReviewResult::empty($provider ?? config('translations.ai.provider', 'openai'), $model);

        if ($pairs === []) {
            return $result;
        }

        $batchSize = max(1, (int) config('translations.ai.review.batch_size', 50));

        foreach (array_chunk($pairs, $batchSize, true) as $batch) {
            $request = new ReviewRequest(
                pairs: $batch,
                sourceLocale: $sourceLocale,
                targetLocale: $target->code,
                provider: $provider,
                model: $model,
            );

            $result = $result->merge($this->reviewBatch($request));
        }

        return $result;
    }

    private function reviewBatch(ReviewRequest $request): ReviewResult
    {
        try {
            $result = $this->reviewer->review($request);
        } catch (Throwable $exception) {
            $this->logUsage($request, null, $exception->getMessage());

            throw $exception;
        }

        $this->logUsage($request, $result);

        return $result;
    }

    /**
     * Collect the source/target pairs for every translated message of the locale,
     * keyed by dotted phrase key. Messages without a source or target value are
     * skipped — there is nothing to review.
     *
     * @return array<string, array{source: string, target: string}>
     */
    private function pairs(Locale $target, ?array $phraseIds): array
    {
        $pairs = [];

        Message::query()
            ->where('locale_id', $target->id)
            ->translated()
            ->when($phraseIds, fn (Builder $query): Builder => $query->whereIn('phrase_id', $phraseIds))
            ->with(['locale', 'phrase.bundle', 'sourceMessage'])
            ->chunkById(500, function (Collection $messages) use (&$pairs): void {
                foreach ($messages as $message) {
                    $source = $message->source;

                    if (! filled($source) || ! filled($message->value)) {
                        continue;
                    }

                    $pairs[$message->phrase->dottedKey()] = [
                        'source' => (string) $source,
                        'target' => (string) $message->value,
                    ];
                }
            });

        return $pairs;
    }

    private function logUsage(ReviewRequest $request, ?ReviewResult $result, ?string $error = null): void
    {
        $model = $result !== null
            ? ($result->model ?? $request->model ?? config('translations.ai.model'))
            : ($request->model ?? config('translations.ai.model'));
        $inputChars = $result !== null ? $result->inputChars : 0;
        $outputChars = $result !== null ? $result->outputChars : 0;
        $provider = $result !== null
            ? $result->provider
            : ($request->provider ?? config('translations.ai.provider'));

        AiUsage::query()->create([
            'provider' => $provider,
            'model' => $model,
            'phrase_id' => null,
            'source_locale' => $request->sourceLocale,
            'target_locale' => $request->targetLocale,
            'input_chars' => $inputChars,
            'output_chars' => $outputChars,
            'cost' => $this->estimator->estimate($model, $inputChars, $outputChars),
            'success' => $error === null,
            'error' => $error,
        ]);
    }
}
