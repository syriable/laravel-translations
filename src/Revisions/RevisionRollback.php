<?php

namespace Syriable\Translations\Revisions;

use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Enums\RevisionReason;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Revision;

class RevisionRollback
{
    public function toRevision(Revision $revision, ?string $by = null): Message
    {
        $message = $revision->message;

        return $this->restore($message, $revision->new_value, $by);
    }

    public function byMember(string $memberId, ?string $from = null, ?string $to = null, ?string $by = null): array
    {
        $revisions = Revision::query()
            ->where('changed_by', $memberId)
            ->between($from, $to)
            ->with('message')
            ->orderByDesc('created_at')
            ->get();

        return $this->rollbackEach($revisions, $by);
    }

    public function afterDate(string $date, ?int $localeId = null, ?string $by = null): array
    {
        $revisions = Revision::query()
            ->where('created_at', '>=', $date)
            ->when($localeId, fn ($query) => $query->forLocale($localeId))
            ->with('message')
            ->orderByDesc('created_at')
            ->get();

        return $this->rollbackEach($revisions, $by);
    }

    private function rollbackEach($revisions, ?string $by): array
    {
        $rolledBack = 0;
        $messages = [];

        foreach ($revisions as $revision) {
            if ($revision->message === null || in_array($revision->message_id, $messages, true)) {
                continue;
            }

            $this->restore($revision->message, $revision->old_value, $by);
            $messages[] = $revision->message_id;
            $rolledBack++;
        }

        return ['rolled_back' => $rolledBack, 'messages_affected' => count($messages)];
    }

    private function restore(Message $message, ?string $value, ?string $by): Message
    {
        Message::stamp(RevisionReason::Rollback->value, $by);

        $message->update([
            'value' => $value,
            'status' => blank($value) ? MessageStatus::Open : MessageStatus::Draft,
        ]);

        Message::clearStamp();

        return $message;
    }
}
