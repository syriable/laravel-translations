<?php

namespace Syriable\Translations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Syriable\Translations\Models\Message;

class MessageSaved
{
    use Dispatchable;

    public function __construct(
        public Message $message,
        public ?string $oldValue = null,
        public ?string $reason = null,
        public ?string $changedBy = null,
        public array $meta = [],
    ) {}
}
