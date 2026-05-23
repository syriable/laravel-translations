<?php

declare(strict_types=1);

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Events\CommentPosted;
use Syriable\Translations\Events\TranslationApproved;
use Syriable\Translations\Events\TranslationForgotten;
use Syriable\Translations\Events\TranslationRejected;
use Syriable\Translations\Events\TranslationSaved;
use Syriable\Translations\Events\TranslationsImported;
use Syriable\Translations\Models\ActivityLog;

/**
 * Records translation write events to the activity log, giving the management
 * UI an audit trail of who changed what and when.
 */
final class LogActivity
{
    public function handle(TranslationSaved|TranslationForgotten|TranslationsImported|TranslationApproved|TranslationRejected|CommentPosted $event): void
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
                ['previous' => $event->previousValue, 'value' => $event->value, 'origin' => $event->origin],
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
            $event instanceof TranslationApproved => $this->record(
                $event->actor,
                'translation.approved',
                $event->locale,
                $event->key,
                [],
            ),
            $event instanceof TranslationRejected => $this->record(
                $event->actor,
                'translation.rejected',
                $event->locale,
                $event->key,
                ['feedback' => $event->feedback],
            ),
            $event instanceof CommentPosted => $this->record(
                $event->actor,
                'comment.posted',
                $event->locale,
                $event->key,
                [],
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
