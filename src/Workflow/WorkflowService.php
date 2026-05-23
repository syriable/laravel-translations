<?php

declare(strict_types=1);

namespace Syriable\Translations\Workflow;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Syriable\Translations\Domain\Enums\ReviewStatus;
use Syriable\Translations\Events\TranslationApproved;
use Syriable\Translations\Events\TranslationRejected;
use Syriable\Translations\Models\TranslationState;
use Syriable\Translations\Support\Actor;

/**
 * Drives the translation review workflow: flagging edits for review and
 * recording approvals/rejections. Review state lives in translation_states,
 * keyed by locale + key; the translated values themselves stay in lang files.
 */
final class WorkflowService
{
    public function __construct(private readonly Dispatcher $events) {}

    public function enabled(): bool
    {
        return config('translations.workflow.enabled', false) === true
            && config('translations.metadata.enabled', true) === true;
    }

    public function flagForReview(string $locale, string $key, bool $aiGenerated = false): TranslationState
    {
        return TranslationState::query()->updateOrCreate(
            ['locale' => $locale, 'key_hash' => TranslationState::hashKey($key)],
            [
                'translation_key' => $key,
                'status' => ReviewStatus::NeedsReview,
                'ai_generated' => $aiGenerated,
                'reviewed_by' => null,
                'reviewer_feedback' => null,
            ],
        );
    }

    public function approve(string $locale, string $key, ?string $reviewer = null): TranslationState
    {
        $reviewer ??= Actor::current();

        $state = TranslationState::query()->updateOrCreate(
            ['locale' => $locale, 'key_hash' => TranslationState::hashKey($key)],
            [
                'translation_key' => $key,
                'status' => ReviewStatus::Approved,
                'reviewed_by' => $reviewer,
                'reviewer_feedback' => null,
            ],
        );

        $this->events->dispatch(new TranslationApproved($locale, $key, $reviewer));

        return $state;
    }

    public function reject(string $locale, string $key, ?string $feedback = null, ?string $reviewer = null): TranslationState
    {
        $reviewer ??= Actor::current();

        $state = TranslationState::query()->updateOrCreate(
            ['locale' => $locale, 'key_hash' => TranslationState::hashKey($key)],
            [
                'translation_key' => $key,
                'status' => ReviewStatus::Rejected,
                'reviewed_by' => $reviewer,
                'reviewer_feedback' => $feedback,
            ],
        );

        $this->events->dispatch(new TranslationRejected($locale, $key, $feedback, $reviewer));

        return $state;
    }

    public function statusFor(string $locale, string $key): ?TranslationState
    {
        return TranslationState::query()->forKey($locale, $key)->first();
    }

    /**
     * @return Builder<TranslationState>
     */
    public function pending(?string $locale = null): Builder
    {
        $query = TranslationState::query()->pending();

        if ($locale !== null) {
            $query->where('locale', $locale);
        }

        return $query;
    }
}
