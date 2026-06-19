<?php

namespace Syriable\Translations\Commands;

use Illuminate\Console\Command;
use Syriable\Translations\Ai\MachineTranslation;
use Syriable\Translations\Jobs\TranslateLocaleJob;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\TranslationManager;

class TranslateCommand extends Command
{
    protected $signature = 'translations:translate {locale : Target locale code} {--key= : Translate a single dotted key} {--all : Translate every untranslated message} {--provider=} {--queue : Dispatch a whole-locale translation to the queue}';

    protected $description = 'Machine-translate messages using the configured AI provider';

    public function handle(MachineTranslation $machine, TranslationManager $manager): int
    {
        if (! config('translations.ai.enabled', false)) {
            $this->components->error(__('translations::messages.translate.disabled'));

            return self::FAILURE;
        }

        $code = $this->argument('locale');
        $locale = Locale::query()->where('code', $code)->first();

        if ($locale === null) {
            $this->components->error(__('translations::messages.translate.unknown_locale', ['code' => $code]));

            return self::FAILURE;
        }

        $options = array_filter(['provider' => $this->option('provider')]);

        if ($key = $this->option('key')) {
            $message = $manager->translate($key, $code, $options);
            $this->components->info($message
                ? __('translations::messages.translate.translated_key', ['key' => $key, 'value' => $message->value])
                : __('translations::messages.translate.no_source', ['key' => $key]));

            return self::SUCCESS;
        }

        if ($this->option('queue')) {
            TranslateLocaleJob::dispatch($locale->id, $options);
            $this->components->info(__('translations::messages.translate.queued', ['code' => $code]));

            return self::SUCCESS;
        }

        $count = $machine->translateOpen($locale, $options);
        $this->components->info(__('translations::messages.translate.done', ['count' => $count, 'code' => $code]));

        return self::SUCCESS;
    }
}
