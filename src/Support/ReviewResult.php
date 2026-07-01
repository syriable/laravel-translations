<?php

namespace Syriable\Translations\Support;

use Syriable\Translations\Enums\ReviewSeverity;

class ReviewResult
{
    /**
     * @param  array<int, ReviewIssue>  $issues
     */
    public function __construct(
        public readonly array $issues,
        public readonly string $provider,
        public readonly ?string $model = null,
        public readonly int $inputChars = 0,
        public readonly int $outputChars = 0,
    ) {}

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    /**
     * The issues reported for a single dotted key.
     *
     * @return array<int, ReviewIssue>
     */
    public function forKey(string $key): array
    {
        return array_values(array_filter($this->issues, fn (ReviewIssue $issue) => $issue->key === $key));
    }

    /**
     * The number of issues per priority, e.g. ['high' => 1, 'medium' => 2, 'low' => 0].
     *
     * @return array<string, int>
     */
    public function countsBySeverity(): array
    {
        $counts = ['high' => 0, 'medium' => 0, 'low' => 0];

        foreach ($this->issues as $issue) {
            $counts[$issue->severity->value]++;
        }

        return $counts;
    }

    /**
     * Merge two results, concatenating their issues and summing usage. Used to
     * fold the per-batch results of a chunked review into a single result.
     */
    public function merge(self $other): self
    {
        return new self(
            issues: array_merge($this->issues, $other->issues),
            provider: $this->provider,
            model: $this->model,
            inputChars: $this->inputChars + $other->inputChars,
            outputChars: $this->outputChars + $other->outputChars,
        );
    }

    public static function empty(string $provider, ?string $model = null): self
    {
        return new self([], $provider, $model);
    }

    /**
     * The highest priority present, or null when there are no issues.
     */
    public function topSeverity(): ?ReviewSeverity
    {
        $top = null;

        foreach ($this->issues as $issue) {
            if ($top === null || $issue->severity->order() > $top->order()) {
                $top = $issue->severity;
            }
        }

        return $top;
    }
}
