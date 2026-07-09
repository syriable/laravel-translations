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

    public function requestReview(Message $message, ?string $requestedBy = null): Message
    {
        return Message::withStamp('review_requested', $requestedBy, [], function () use ($message): Message {
            $message->update([
                'status' => MessageStatus::PendingReview,
            ]);

            return $message;
        });
    }

    public function approve(Message $message, ?string $reviewer = null): Message
    {
        return Message::withStamp('approval', $reviewer, [], function (?string $resolvedBy) use ($message): Message {
            $message->update([
                'status' => MessageStatus::Approved,
                'reviewed_by' => $resolvedBy,
                'review_note' => null,
            ]);

            return $message;
        });
    }

    public function reject(Message $message, string $note, ?string $reviewer = null): Message
    {
        return Message::withStamp('rejection', $reviewer, ['note' => $note], function (?string $resolvedBy) use ($message, $note): Message {
            $message->update([
                'status' => MessageStatus::PendingReview,
                'reviewed_by' => $resolvedBy,
                'review_note' => $note,
            ]);

            $message->comment($note, $resolvedBy, ['type' => 'rejection']);

            return $message;
        });
    }
}
