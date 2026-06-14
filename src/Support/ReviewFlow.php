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
        return Message::withStamp('approval', $reviewer, [], function () use ($message, $reviewer): Message {
            $message->update([
                'status' => MessageStatus::Approved,
                'reviewed_by' => $reviewer,
                'review_note' => null,
            ]);

            return $message;
        });
    }

    public function reject(Message $message, string $note, ?string $reviewer = null): Message
    {
        return Message::withStamp('rejection', $reviewer, [], function () use ($message, $note, $reviewer): Message {
            $message->update([
                'status' => MessageStatus::PendingReview,
                'reviewed_by' => $reviewer,
                'review_note' => $note,
            ]);

            return $message;
        });
    }
}
