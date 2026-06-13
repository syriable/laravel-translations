<?php

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Enums\RevisionReason;
use Syriable\Translations\Events\MessageSaved;
use Syriable\Translations\Models\Revision;

class RecordRevision
{
    public function handle(MessageSaved $event): void
    {
        if (! config('translations.revisions.enabled', true)) {
            return;
        }

        if ($event->oldValue === $event->message->value) {
            return;
        }

        Revision::query()->create([
            'message_id' => $event->message->id,
            'old_value' => $event->oldValue,
            'new_value' => $event->message->value,
            'reason' => $this->reason($event->reason),
            'changed_by' => $event->changedBy,
            'meta' => $event->meta,
        ]);
    }

    private function reason(?string $reason): RevisionReason
    {
        return RevisionReason::tryFrom((string) $reason) ?? RevisionReason::Manual;
    }
}
