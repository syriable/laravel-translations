<?php

namespace Syriable\Translations\Quality;

use Syriable\Translations\Contracts\QualityCheck;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Support\PlaceholderScanner;

abstract class Check implements QualityCheck
{
    protected PlaceholderScanner $scanner;

    public function __construct()
    {
        $this->scanner = new PlaceholderScanner;
    }

    public function fixable(): bool
    {
        return false;
    }

    public function fix(Message $message, Message $source): ?string
    {
        return null;
    }

    protected function bothFilled(Message $message, Message $source): bool
    {
        return filled($message->value) && filled($source->value);
    }
}
