<?php

declare(strict_types=1);

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Events\TranslationSaved;
use Syriable\Translations\Storage\StorageManager;
use Syriable\Translations\Validation\ValidationIssueRecorder;
use Syriable\Translations\Validation\ValidationPipeline;

/**
 * Re-validates a single translation whenever it is saved and refreshes its
 * stored validation issues, so the management UI always reflects the latest
 * state without a full validation run.
 */
final class ValidateOnSave
{
    public function __construct(
        private readonly StorageManager $storage,
        private readonly ValidationPipeline $pipeline,
        private readonly ValidationIssueRecorder $recorder,
    ) {}

    public function handle(TranslationSaved $event): void
    {
        if (! $this->recorder->enabled()) {
            return;
        }

        $sourceLocale = (string) config('translations.locales.source', 'en');

        if ($event->locale === $sourceLocale) {
            return;
        }

        $sourceValue = $this->storage->driver()->read(new Locale($sourceLocale))->get($event->key);

        $report = $this->pipeline->validateKey($event->locale, $event->key, $sourceValue, $event->value);

        $this->recorder->recordForKey($event->locale, $event->key, $report);
    }
}
