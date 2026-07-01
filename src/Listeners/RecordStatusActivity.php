<?php

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Events\MessageStatusChanged;
use Syriable\Translations\Support\ActivityRecorder;

class RecordStatusActivity
{
    public function __construct(
        private readonly ActivityRecorder $recorder,
    ) {}

    public function handle(MessageStatusChanged $event): void
    {
        if (! config('translations.activities.enabled', true)) {
            return;
        }

        $action = $event->reason === 'review_requested' ? 'review_requested' : 'status_changed';

        $this->recorder->log(
            $action,
            $event->message,
            array_merge([
                'from' => $event->oldStatus?->value,
                'to' => $event->newStatus->value,
                'reason' => $event->reason,
            ], $event->meta),
            $event->changedBy,
        );
    }
}
