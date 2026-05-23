<?php

declare(strict_types=1);

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Domain\Enums\RevisionType;
use Syriable\Translations\Events\TranslationForgotten;
use Syriable\Translations\Events\TranslationSaved;
use Syriable\Translations\Models\TranslationRevision;

/**
 * Records a revision for every individual translation edit, capturing the old
 * and new value plus who made the change.
 */
final class RecordRevision
{
    public function handle(TranslationSaved|TranslationForgotten $event): void
    {
        if (config('translations.metadata.enabled', true) !== true) {
            return;
        }

        [$old, $new, $type] = match (true) {
            $event instanceof TranslationSaved => [
                $event->previousValue,
                $event->value,
                $event->created ? RevisionType::Created : RevisionType::Updated,
            ],
            $event instanceof TranslationForgotten => [
                $event->previousValue,
                null,
                RevisionType::Deleted,
            ],
        };

        TranslationRevision::create([
            'locale' => $event->locale,
            'translation_key' => $event->key,
            'key_hash' => TranslationRevision::hashKey($event->key),
            'old_value' => $old,
            'new_value' => $new,
            'change_type' => $type,
            'changed_by' => $event->actor,
        ]);
    }
}
