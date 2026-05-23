<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain;

/**
 * The complete set of translation entries for a single locale, held as a flat
 * map of fully-qualified dotted keys to their values. A null value represents a
 * key that exists but has not yet been translated.
 */
final class LocaleCatalog
{
    /**
     * @param  array<string, string|null>  $entries
     */
    public function __construct(
        public readonly Locale $locale,
        private array $entries = [],
    ) {}

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->entries);
    }

    public function get(string $key): ?string
    {
        return $this->entries[$key] ?? null;
    }

    public function put(string $key, ?string $value): self
    {
        $this->entries[$key] = $value;

        return $this;
    }

    public function forget(string $key): self
    {
        unset($this->entries[$key]);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->entries);
    }

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        return $this->entries;
    }

    /**
     * @return list<Translation>
     */
    public function translations(): array
    {
        $translations = [];

        foreach ($this->entries as $key => $value) {
            $translations[] = new Translation((string) $key, $value);
        }

        return $translations;
    }

    /**
     * Keys that exist but have an empty or null value.
     *
     * @return list<string>
     */
    public function untranslatedKeys(): array
    {
        $keys = [];

        foreach ($this->entries as $key => $value) {
            if ($value === null || $value === '') {
                $keys[] = (string) $key;
            }
        }

        return $keys;
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function translatedCount(): int
    {
        return $this->count() - count($this->untranslatedKeys());
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }
}
