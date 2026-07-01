<?php

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Events\CommentPosted;
use Syriable\Translations\Support\ActivityRecorder;

class RecordCommentActivity
{
    public function __construct(
        private readonly ActivityRecorder $recorder,
    ) {}

    public function handle(CommentPosted $event): void
    {
        if (! config('translations.activities.enabled', true)) {
            return;
        }

        $this->recorder->log(
            'comment_added',
            $event->comment->message,
            [
                'comment_id' => $event->comment->id,
                'body' => $event->comment->body,
            ],
            $event->comment->member_id,
        );
    }
}
