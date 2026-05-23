<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Models\TranslationState;
use Syriable\Translations\Workflow\WorkflowService;

final class ReviewCommand extends Command
{
    protected $signature = 'translations:review
        {--locale= : Limit to a single locale}
        {--strict : Exit with a failure when any translations still need review}';

    protected $description = 'Report translations awaiting review in the approval workflow';

    public function handle(WorkflowService $workflow): int
    {
        if (! $workflow->enabled()) {
            $this->warn('The review workflow is disabled.');

            return self::SUCCESS;
        }

        /** @var string|null $locale */
        $locale = $this->option('locale') ?: null;

        $pending = $workflow->pending($locale)
            ->orderBy('locale')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No translations are awaiting review.');

            return self::SUCCESS;
        }

        $this->table(
            ['Locale', 'Key', 'AI'],
            $pending->map(static fn (TranslationState $state): array => [
                $state->locale,
                $state->translation_key,
                $state->ai_generated ? 'yes' : '',
            ])->all(),
        );

        $this->line("{$pending->count()} translation(s) awaiting review.");

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }
}
