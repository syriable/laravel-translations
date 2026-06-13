<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Ai\MachineTranslation;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\TranslationManager;

class TranslateCommand extends Command
{
    protected $signature = 'translations:translate {locale : Target locale code} {--key= : Translate a single dotted key} {--all : Translate every untranslated message} {--provider=}';

    protected $description = 'Machine-translate messages using the configured AI provider';

    public function handle(MachineTranslation $machine, TranslationManager $manager): int
    {
        if (! config('translations.ai.enabled', false)) {
            $this->components->error('AI translation is disabled. Set TRANSLATIONS_AI=true to enable it.');

            return self::FAILURE;
        }

        $code = $this->argument('locale');
        $locale = Locale::query()->where('code', $code)->first();

        if ($locale === null) {
            $this->components->error("Unknown locale [{$code}].");

            return self::FAILURE;
        }

        $options = array_filter(['provider' => $this->option('provider')]);

        if ($key = $this->option('key')) {
            $message = $manager->translate($key, $code, $options);
            $this->components->info($message ? "Translated [{$key}]: {$message->value}" : "No source value for [{$key}].");

            return self::SUCCESS;
        }

        $count = $machine->translateOpen($locale, $options);
        $this->components->info("Translated {$count} messages into [{$code}].");

        return self::SUCCESS;
    }
}
