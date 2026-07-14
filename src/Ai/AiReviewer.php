<?php

namespace Syriable\Translations\Ai;

use Syriable\Translations\Contracts\Reviewer;
use Syriable\Translations\Support\ReviewIssue;
use Syriable\Translations\Support\ReviewRequest;
use Syriable\Translations\Support\ReviewResult;

class AiReviewer implements Reviewer
{
    public function __construct(
        private readonly ReviewParser $parser = new ReviewParser,
    ) {}

    public function review(ReviewRequest $request): ReviewResult
    {
        $requested = AiProviders::sanitize($request->provider);
        $provider = $requested ?? config('translations.ai.provider', 'openai');
        $model = $request->model ?? config('translations.ai.model');

        $prompt = $this->prompt($request->pairs);

        $agent = new TranslationReviewAgent($request->sourceLocale, $request->targetLocale);
        $response = $agent->prompt($prompt, provider: $provider, model: $model);

        $issues = $this->parser->parse($response['issues'] ?? [], array_keys($request->pairs));

        $outputChars = array_sum(array_map(
            fn (ReviewIssue $issue): int => mb_strlen($issue->description.(string) $issue->suggestion),
            $issues,
        ));

        return new ReviewResult(
            issues: $issues,
            provider: $provider,
            model: $model,
            inputChars: mb_strlen($prompt),
            outputChars: $outputChars,
        );
    }

    /**
     * Render the source/target pairs as fenced review lines. Wrapping the
     * untrusted source and target text in «» mirrors the prompt-injection
     * guard used when building translation prompts.
     *
     * @param  array<string, array{source: string, target: string}>  $pairs
     */
    private function prompt(array $pairs): string
    {
        $lines = [];

        foreach ($pairs as $key => $pair) {
            $source = $this->fence($pair['source']);
            $target = $this->fence($pair['target']);

            $lines[] = "{$key}: «{$source}» → «{$target}»";
        }

        return implode("\n", $lines);
    }

    private function fence(string $value): string
    {
        return str_replace(['«', '»'], '', $value);
    }
}
