<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Quality\Inspector;

class ValidateCommand extends Command
{
    protected $signature = 'translations:validate {--locale= : Filter by locale code} {--fix : Auto-fix fixable issues}';

    protected $description = 'Run quality checks against translated messages';

    public function handle(Inspector $inspector): int
    {
        $localeId = $this->localeId();
        $stats = $inspector->scan($localeId);

        $this->table(
            ['Checked', 'Errors', 'Warnings', 'Info'],
            [[$stats['checked'], $stats['error'], $stats['warning'], $stats['info']]],
        );

        if ($this->option('fix')) {
            $fixed = $this->fixAll($inspector, $localeId);
            $this->components->info("Auto-fixed {$fixed} issues.");
        }

        return $stats['error'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function fixAll(Inspector $inspector, ?int $localeId): int
    {
        $fixed = 0;

        \Syriable\Translations\Models\QualityIssue::query()
            ->where('fixable', true)
            ->when($localeId, fn ($query) => $query->where('locale_id', $localeId))
            ->each(function ($issue) use ($inspector, &$fixed): void {
                if ($inspector->fix($issue)) {
                    $fixed++;
                }
            });

        return $fixed;
    }

    private function localeId(): ?int
    {
        if (! $code = $this->option('locale')) {
            return null;
        }

        return optional(Locale::query()->where('code', $code)->first())->id;
    }
}
