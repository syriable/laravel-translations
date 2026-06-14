<?php

namespace Syriable\Translations\Quality\Checks;

use Illuminate\Support\Collection;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\Term;
use Syriable\Translations\Quality\Check;
use Syriable\Translations\Support\Issue;

class GlossaryCheck extends Check
{
    private ?Collection $terms = null;

    public function key(): string
    {
        return 'glossary';
    }

    public function inspect(Message $message, Message $source): ?Issue
    {
        if (! $this->bothFilled($message, $source)) {
            return null;
        }

        $violations = [];

        foreach ($this->terms() as $term) {
            if (! $this->mentions($source->value, $term)) {
                continue;
            }

            $definition = $term->definitionFor($message->locale_id);

            if ($definition === null) {
                continue;
            }

            if (! str_contains(mb_strtolower($message->value), mb_strtolower($definition->value))) {
                $violations[] = "{$term->source} → {$definition->value}";
            }
        }

        if ($violations === []) {
            return null;
        }

        return Issue::warning(
            $this->key(),
            'Glossary terms were not applied: '.implode(', ', $violations),
            ['violations' => $violations],
        );
    }

    private function terms(): Collection
    {
        return $this->terms ??= Term::query()->with('definitions')->get();
    }

    private function mentions(string $text, Term $term): bool
    {
        $haystack = $term->case_sensitive ? $text : mb_strtolower($text);
        $needle = $term->case_sensitive ? $term->source : mb_strtolower($term->source);

        if ($term->whole_word) {
            return (bool) preg_match('/\b'.preg_quote($needle, '/').'\b/u', $haystack);
        }

        return str_contains($haystack, $needle);
    }
}
