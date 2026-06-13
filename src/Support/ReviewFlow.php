<?php

namespace Syriable\Translations\Support;

use Syriable\Translations\Enums\MemberRole;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Models\Message;

class ReviewFlow
{
    public function statusForSave(?MemberRole $role = null): MessageStatus
    {
        if (! config('translations.review.enabled', true)) {
            return MessageStatus::Approved;
        }

        if ($role !== null && $role->canReview()) {
            return MessageStatus::Approved;
        }

        return MessageStatus::PendingReview;
    }

    public function approve(Message $message, ?string $reviewer = null): Message
    {
        Message::stamp('approval', $reviewer);

        $message->update([
            'status' => MessageStatus::Approved,
            'reviewed_by' => $reviewer,
            'review_note' => null,
        ]);

        Message::clearStamp();

        return $message;
    }

    public function reject(Message $message, string $note, ?string $reviewer = null): Message
    {
        Message::stamp('rejection', $reviewer);

        $message->update([
            'status' => MessageStatus::PendingReview,
            'reviewed_by' => $reviewer,
            'review_note' => $note,
        ]);

        Message::clearStamp();

        return $message;
    }
}
