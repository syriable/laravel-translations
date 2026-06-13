<?php

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Models\Message;
use Syriable\Translations\Support\Issue;

interface QualityCheck
{
    public function key(): string;

    public function inspect(Message $message, Message $source): ?Issue;

    public function fixable(): bool;

    public function fix(Message $message, Message $source): ?string;
}
