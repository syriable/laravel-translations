<?php

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Events\MessageSaved;
use Syriable\Translations\Quality\Inspector;

class RunQualityChecks
{
    public function __construct(
        private readonly Inspector $inspector,
    ) {}

    public function handle(MessageSaved $event): void
    {
        if (! config('translations.quality.run_on_save', true)) {
            return;
        }

        if (blank($event->message->value)) {
            $event->message->issues()->delete();

            return;
        }

        $this->inspector->inspectAndStore($event->message);
    }
}
