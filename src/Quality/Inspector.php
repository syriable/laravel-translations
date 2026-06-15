<?php

namespace Syriable\Translations\Quality;

use Syriable\Translations\Contracts\QualityCheck;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\QualityIssue;
use Syriable\Translations\Support\Issue;

class Inspector
{
    /** @var array<int, QualityCheck> */
    private array $checks;

    public function __construct(?array $checks = null)
    {
        $this->checks = array_map(
            fn (string $class) => app($class),
            $checks ?? config('translations.quality.checks', []),
        );
    }

    /** @return array<int, Issue> */
    public function inspect(Message $message): array
    {
        $source = $this->sourceFor($message);

        if ($source === null || $source->is($message)) {
            return [];
        }

        $issues = [];

        foreach ($this->checks as $check) {
            $issue = $check->inspect($message, $source);

            if ($issue !== null) {
                $issues[] = $issue;
            }
        }

        return $issues;
    }

    public function inspectAndStore(Message $message): array
    {
        $issues = $this->inspect($message);

        $message->issues()->delete();

        foreach ($issues as $issue) {
            QualityIssue::query()->create([
                'message_id' => $message->id,
                'locale_id' => $message->locale_id,
                'check' => $issue->check,
                'severity' => $issue->severity,
                'detail' => $issue->message,
                'suggestion' => $issue->suggestion,
                'fixable' => $issue->fixable,
                'meta' => $issue->meta,
            ]);
        }

        return $issues;
    }

    public function scan(?int $localeId = null): array
    {
        $stats = ['error' => 0, 'warning' => 0, 'info' => 0, 'checked' => 0];

        Message::query()
            ->translated()
            ->when($localeId, fn ($query) => $query->where('locale_id', $localeId))
            ->with(['phrase', 'locale'])
            ->chunkById(200, function ($messages) use (&$stats): void {
                foreach ($messages as $message) {
                    foreach ($this->inspectAndStore($message) as $issue) {
                        $stats[$issue->severity->value]++;
                    }

                    $stats['checked']++;
                }
            });

        return $stats;
    }

    public function fix(QualityIssue $issue): bool
    {
        if (! $issue->fixable) {
            return false;
        }

        $check = collect($this->checks)->first(fn (QualityCheck $candidate) => $candidate->key() === $issue->check);

        if ($check === null || ! $check->fixable()) {
            return false;
        }

        $message = $issue->message;
        $source = $this->sourceFor($message);

        if ($source === null) {
            return false;
        }

        $fixed = $check->fix($message, $source);

        if ($fixed === null) {
            return false;
        }

        $message->update(['value' => $fixed]);
        $issue->delete();

        return true;
    }

    private function sourceFor(Message $message): ?Message
    {
        $source = Locale::source();

        if ($source === null) {
            return null;
        }

        return Message::query()
            ->where('phrase_id', $message->phrase_id)
            ->where('locale_id', $source->id)
            ->with('locale')
            ->first();
    }
}
