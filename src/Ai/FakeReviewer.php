<?php

namespace Syriable\Translations\Ai;

use Closure;
use Syriable\Translations\Contracts\Reviewer;
use Syriable\Translations\Support\ReviewIssue;
use Syriable\Translations\Support\ReviewRequest;
use Syriable\Translations\Support\ReviewResult;

class FakeReviewer implements Reviewer
{
    /** @var array<int, ReviewRequest> */
    public array $requests = [];

    /**
     * @param  (Closure(ReviewRequest): array<int, ReviewIssue>)|null  $using
     */
    public function __construct(
        private readonly ?Closure $using = null,
    ) {}

    public function review(ReviewRequest $request): ReviewResult
    {
        $this->requests[] = $request;

        $issues = $this->using ? ($this->using)($request) : [];

        return new ReviewResult(
            issues: $issues,
            provider: $request->provider ?? 'fake',
            model: $request->model ?? 'fake',
            inputChars: array_sum(array_map(
                fn (array $pair): int => mb_strlen($pair['source'].$pair['target']),
                $request->pairs,
            )),
            outputChars: 0,
        );
    }
}
