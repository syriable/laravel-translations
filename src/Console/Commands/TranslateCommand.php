<?php

declare(strict_types=1);

namespace Syriable\Translations\Console\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Ai\AiTranslationService;

final class TranslateCommand extends Command
{
    protected $signature = 'translations:translate
        {locale : The target locale to fill}
        {--keys=* : Limit translation to specific keys}';

    protected $description = 'Use the configured AI translator to fill missing translations for a locale';

    public function handle(AiTranslationService $service): int
    {
        if (! $service->available()) {
            $this->error('No AI translator is configured. Bind a Translator implementation and enable translations.ai.');

            return self::FAILURE;
        }

        /** @var string $locale */
        $locale = $this->argument('locale');

        /** @var list<string> $keys */
        $keys = (array) $this->option('keys');

        $result = $service->translateMissing($locale, $keys === [] ? null : $keys);

        $this->info("Translated {$result->translated} key(s); skipped {$result->skipped}.");

        return self::SUCCESS;
    }
}
