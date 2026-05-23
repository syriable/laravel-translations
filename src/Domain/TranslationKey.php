<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain;

use Stringable;

/**
 * Identity of a translation key as it appears in source or storage, e.g.
 * "messages.welcome", "Welcome back!", or "package::messages.welcome".
 */
final readonly class TranslationKey implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = trim($value);
    }

    public static function of(string $value): self
    {
        return new self($value);
    }

    public function isNamespaced(): bool
    {
        return str_contains($this->value, '::');
    }

    public function namespace(): ?string
    {
        if (! $this->isNamespaced()) {
            return null;
        }

        return explode('::', $this->value, 2)[0];
    }

    /**
     * The key without its namespace prefix, e.g. "messages.welcome".
     */
    public function path(): string
    {
        return $this->isNamespaced()
            ? explode('::', $this->value, 2)[1]
            : $this->value;
    }

    /**
     * The leading group segment of a dotted key, used as a placement hint.
     * Returns null for keys that have no dotted group (JSON-style keys).
     */
    public function group(): ?string
    {
        $path = $this->path();

        if (! str_contains($path, '.')) {
            return null;
        }

        return explode('.', $path, 2)[0];
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
