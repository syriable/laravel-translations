<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Ai\MachineReview;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Support\ReviewIssue;

class ReviewCommand extends Command
{
    protected $signature = 'translations:ai-review {locale : Target locale code} {--provider= : Request a specific AI provider}';

    protected $description = 'AI-review translated messages for quality issues (phrasing, gender, pluralization, consistency)';

    public function handle(MachineReview $review): int
    {
        if (! config('translations.ai.enabled', false)) {
            $this->components->error(__('translations::messages.translate.disabled'));

            return self::FAILURE;
        }

        $code = $this->argument('locale');
        $locale = Locale::query()->where('code', $code)->first();

        if ($locale === null) {
            $this->components->error(__('translations::messages.translate.unknown_locale', ['code' => $code]));

            return self::FAILURE;
        }

        $options = array_filter(['provider' => $this->option('provider')]);

        $result = $review->review($locale, $options);

        if (! $result->hasIssues()) {
            $this->components->info(__('translations::messages.ai_review.clean', ['code' => $code]));

            return self::SUCCESS;
        }

        $this->table(
            [
                __('translations::messages.ai_review.table.key'),
                __('translations::messages.ai_review.table.severity'),
                __('translations::messages.ai_review.table.detail'),
                __('translations::messages.ai_review.table.suggestion'),
            ],
            array_map(fn (ReviewIssue $issue) => [
                $issue->key,
                $issue->severity->value,
                $issue->description,
                $issue->suggestion ?? '',
            ], $result->issues),
        );

        $counts = $result->countsBySeverity();
        $this->components->info(__('translations::messages.ai_review.done', [
            'code' => $code,
            'errors' => $counts['error'],
            'warnings' => $counts['warning'],
            'info' => $counts['info'],
        ]));

        return $counts['error'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
