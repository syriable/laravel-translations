<?php

declare(strict_types=1);

namespace Syriable\Translations\Extraction;

use Syriable\Translations\Domain\ExtractedKey;
use Syriable\Translations\Domain\SourceReference;

/**
 * The aggregated outcome of an extraction run: every distinct translation key
 * discovered in the scanned source, with all of its references.
 */
final readonly class ExtractionResult
{
    /**
     * @param  array<string, ExtractedKey>  $keys  keyed by the key string
     */
    public function __construct(private array $keys = []) {}

    /**
     * @return list<ExtractedKey>
     */
    public function all(): array
    {
        return array_values($this->keys);
    }

    /**
     * @return list<string>
     */
    public function keyStrings(): array
    {
        return array_keys($this->keys);
    }

    public function has(string $key): bool
    {
        return isset($this->keys[$key]);
    }

    public function get(string $key): ?ExtractedKey
    {
        return $this->keys[$key] ?? null;
    }

    /**
     * @return list<SourceReference>
     */
    public function referencesFor(string $key): array
    {
        return $this->keys[$key]->references ?? [];
    }

    public function count(): int
    {
        return count($this->keys);
    }

    public function isEmpty(): bool
    {
        return $this->keys === [];
    }
}
