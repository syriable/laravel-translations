<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain;

/**
 * The full multi-locale translation catalog: a collection of per-locale
 * catalogs plus knowledge of which locale is the source of truth.
 */
final class Catalog
{
    /**
     * @param  array<string, LocaleCatalog>  $locales  keyed by locale code
     */
    public function __construct(
        private array $locales = [],
        private ?string $sourceLocale = null,
    ) {}

    public function add(LocaleCatalog $catalog): self
    {
        $this->locales[$catalog->locale->code] = $catalog;

        return $this;
    }

    public function withSource(string $sourceLocale): self
    {
        $this->sourceLocale = $sourceLocale;

        return $this;
    }

    public function has(string $code): bool
    {
        return isset($this->locales[$code]);
    }

    public function locale(string $code): ?LocaleCatalog
    {
        return $this->locales[$code] ?? null;
    }

    public function source(): ?LocaleCatalog
    {
        if ($this->sourceLocale === null) {
            return null;
        }

        return $this->locales[$this->sourceLocale] ?? null;
    }

    /**
     * @return list<string>
     */
    public function localeCodes(): array
    {
        return array_keys($this->locales);
    }

    /**
     * @return array<string, LocaleCatalog>
     */
    public function all(): array
    {
        return $this->locales;
    }

    /**
     * The union of every key across every locale.
     *
     * @return list<string>
     */
    public function allKeys(): array
    {
        $keys = [];

        foreach ($this->locales as $catalog) {
            foreach ($catalog->keys() as $key) {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }

    public function isEmpty(): bool
    {
        return $this->locales === [];
    }
}
