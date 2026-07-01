<?php

namespace Syriable\Translations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Models\Message;

class MessageStatusChanged
{
    use Dispatchable;

    public function __construct(
        public Message $message,
        public ?MessageStatus $oldStatus,
        public MessageStatus $newStatus,
        public ?string $reason = null,
        public ?string $changedBy = null,
        public array $meta = [],
    ) {}
}
