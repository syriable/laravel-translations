<?php

declare(strict_types=1);

namespace Syriable\Translations\Listeners;

use Syriable\Translations\Events\TranslationSaved;
use Syriable\Translations\Workflow\WorkflowService;

/**
 * Flags a translation for review whenever it is saved (manual or AI), so a
 * reviewer can approve it. Source-locale edits are not flagged.
 */
final class FlagForReview
{
    public function __construct(private readonly WorkflowService $workflow) {}

    public function handle(TranslationSaved $event): void
    {
        if (! $this->workflow->enabled()) {
            return;
        }

        if ($event->locale === (string) config('translations.locales.source', 'en')) {
            return;
        }

        $this->workflow->flagForReview($event->locale, $event->key, $event->origin === 'ai');
    }
}
