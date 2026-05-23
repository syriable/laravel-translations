<?php

declare(strict_types=1);

namespace Syriable\Translations\Management;

/**
 * Summary of a catalog transfer between two storage drivers.
 */
final class TransferReport
{
    /**
     * @var array<string, int>
     */
    private array $keysPerLocale = [];

    public function record(string $locale, int $keys): void
    {
        $this->keysPerLocale[$locale] = $keys;
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return array_keys($this->keysPerLocale);
    }

    public function localeCount(): int
    {
        return count($this->keysPerLocale);
    }

    public function totalKeys(): int
    {
        return array_sum($this->keysPerLocale);
    }
}
