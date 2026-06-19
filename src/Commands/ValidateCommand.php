<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Jobs\ScanQualityJob;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\QualityIssue;
use Syriable\Translations\Quality\Inspector;

class ValidateCommand extends Command
{
    protected $signature = 'translations:validate {--locale= : Filter by locale code} {--fix : Auto-fix fixable issues} {--queue : Dispatch the scan to the queue instead of running inline}';

    protected $description = 'Run quality checks against translated messages';

    public function handle(Inspector $inspector): int
    {
        $localeId = $this->localeId();

        if ($this->option('queue')) {
            ScanQualityJob::dispatch($localeId);
            $this->components->info(__('translations::messages.validate.queued'));

            return self::SUCCESS;
        }

        $stats = $inspector->scan($localeId);

        $this->table(
            [
                __('translations::messages.validate.table.checked'),
                __('translations::messages.validate.table.errors'),
                __('translations::messages.validate.table.warnings'),
                __('translations::messages.validate.table.info'),
            ],
            [[$stats['checked'], $stats['error'], $stats['warning'], $stats['info']]],
        );

        if ($this->option('fix')) {
            $fixed = $this->fixAll($inspector, $localeId);
            $this->components->info(__('translations::messages.validate.fixed', ['count' => $fixed]));
        }

        return $stats['error'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function fixAll(Inspector $inspector, ?int $localeId): int
    {
        $fixed = 0;

        QualityIssue::query()
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
