<?php

declare(strict_types=1);

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Events\TranslationForgotten;
use Syriable\Translations\Events\TranslationSaved;
use Syriable\Translations\Events\TranslationsImported;
use Syriable\Translations\Models\ActivityLog;

/**
 * Records translation write events to the activity log, giving the management
 * UI an audit trail of who changed what and when.
 */
final class LogActivity
{
    public function handle(TranslationSaved|TranslationForgotten|TranslationsImported $event): void
    {
        if (config('translations.metadata.enabled', true) !== true) {
            return;
        }

        match (true) {
            $event instanceof TranslationSaved => $this->record(
                $event->actor,
                $event->created ? 'translation.created' : 'translation.updated',
                $event->locale,
                $event->key,
                ['previous' => $event->previousValue, 'value' => $event->value],
            ),
            $event instanceof TranslationForgotten => $this->record(
                $event->actor,
                'translation.forgotten',
                $event->locale,
                $event->key,
                ['previous' => $event->previousValue],
            ),
            $event instanceof TranslationsImported => $this->record(
                $event->actor,
                'translations.imported',
                null,
                null,
                ['locales' => $event->locales, 'driver' => $event->driver],
            ),
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function record(?string $actor, string $action, ?string $locale, ?string $key, array $metadata): void
    {
        ActivityLog::create([
            'user_id' => $actor,
            'action' => $action,
            'locale' => $locale,
            'translation_key' => $key,
            'metadata' => $metadata,
        ]);
    }
}
