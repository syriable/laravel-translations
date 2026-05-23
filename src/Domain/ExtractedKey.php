<?php

declare(strict_types=1);

namespace Syriable\Translations\Domain;

/**
 * A translation key discovered in source code, together with every place it
 * is referenced. Produced by scanners and aggregated by the extraction pipeline.
 */
final readonly class ExtractedKey
{
    /**
     * @param  list<SourceReference>  $references
     */
    public function __construct(
        public TranslationKey $key,
        public array $references = [],
        public bool $isChoice = false,
    ) {}

    /**
     * Merge another occurrence of the same key, combining references.
     */
    public function mergedWith(self $other): self
    {
        return new self(
            $this->key,
            [...$this->references, ...$other->references],
            $this->isChoice || $other->isChoice,
        );
    }

    public function referenceCount(): int
    {
        return count($this->references);
    }
}
