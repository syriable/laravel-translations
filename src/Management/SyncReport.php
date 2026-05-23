<?php

declare(strict_types=1);

namespace Syriable\Translations\Management;

/**
 * Records what a synchronization run changed (or would change, when dry).
 */
final class SyncReport
{
    /**
     * @var array<string, list<string>>
     */
    private array $added = [];

    /**
     * @var array<string, list<string>>
     */
    private array $pruned = [];

    public function __construct(public readonly bool $dryRun = false) {}

    /**
     * @param  list<string>  $added
     * @param  list<string>  $pruned
     */
    public function record(string $locale, array $added, array $pruned): void
    {
        if ($added !== []) {
            $this->added[$locale] = $added;
        }

        if ($pruned !== []) {
            $this->pruned[$locale] = $pruned;
        }
    }

    /**
     * @return list<string>
     */
    public function addedFor(string $locale): array
    {
        return $this->added[$locale] ?? [];
    }

    /**
     * @return list<string>
     */
    public function prunedFor(string $locale): array
    {
        return $this->pruned[$locale] ?? [];
    }

    /**
     * @return list<string>
     */
    public function locales(): array
    {
        return array_values(array_unique([...array_keys($this->added), ...array_keys($this->pruned)]));
    }

    public function totalAdded(): int
    {
        return array_sum(array_map('count', $this->added));
    }

    public function totalPruned(): int
    {
        return array_sum(array_map('count', $this->pruned));
    }

    public function isEmpty(): bool
    {
        return $this->added === [] && $this->pruned === [];
    }
}
