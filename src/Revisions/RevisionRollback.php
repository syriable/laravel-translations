<?php

namespace Syriable\Translations\Revisions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Enums\RevisionReason;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Revision;

class RevisionRollback
{
    public function toRevision(Revision $revision, ?string $by = null): Message
    {
        $message = $revision->message;

        if ($message === null) {
            throw new RuntimeException('Revision is missing its message.');
        }

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
            ->when($localeId, fn (Builder $query): Builder => $query->forLocale($localeId))
            ->with('message')
            ->orderByDesc('created_at')
            ->get();

        return $this->rollbackEach($revisions, $by);
    }

    /**
     * @param  Collection<int, Revision>  $revisions
     * @return array{rolled_back: int, messages_affected: int}
     */
    private function rollbackEach(Collection $revisions, ?string $by): array
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
        return Message::withStamp(RevisionReason::Rollback->value, $by, [], function () use ($message, $value): Message {
            $message->update([
                'value' => $value,
                'status' => blank($value) ? MessageStatus::Open : MessageStatus::Draft,
            ]);

            return $message;
        });
    }
}
