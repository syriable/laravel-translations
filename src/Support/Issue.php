<?php

namespace Syriable\Translations\Support;

use Syriable\Translations\Enums\Severity;

class Issue
{
    public function __construct(
        public readonly string $check,
        public readonly Severity $severity,
        public readonly string $message,
        public readonly ?string $suggestion = null,
        public readonly bool $fixable = false,
        public readonly array $meta = [],
    ) {}

    public static function error(string $check, string $message, array $meta = []): self
    {
        return new self($check, Severity::Error, $message, meta: $meta);
    }

    public static function warning(string $check, string $message, array $meta = []): self
    {
        return new self($check, Severity::Warning, $message, meta: $meta);
    }

    public static function info(string $check, string $message, array $meta = []): self
    {
        return new self($check, Severity::Info, $message, meta: $meta);
    }
}
