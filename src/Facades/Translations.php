<?php

namespace Syriable\Translations\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Syriable\Translations\Ai\MachineTranslation;
use Syriable\Translations\Analytics\Insights;
use Syriable\Translations\Glossary\Glossary;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Quality\Inspector;
use Syriable\Translations\Revisions\RevisionRollback;
use Syriable\Translations\Support\ExportSummary;
use Syriable\Translations\Support\ImportSummary;
use Syriable\Translations\Support\ReviewFlow;
use Syriable\Translations\TranslationManager;

/**
 * @method static string|null get(string $key, string|null $locale = null)
 * @method static bool has(string $key, string|null $locale = null)
 * @method static Message set(string $key, string $value, string|null $locale = null, array $options = [])
 * @method static void forget(string $key, string|null $locale = null)
 * @method static array all(string|null $locale = null)
 * @method static Collection similar(string $key, array $options = [])
 * @method static Collection locales()
 * @method static Locale addLocale(string $code, array $attributes = [])
 * @method static ImportSummary import(array $options = [])
 * @method static ExportSummary export(array $options = [])
 * @method static Message|null translate(string $key, string $locale, array $options = [])
 * @method static MachineTranslation ai()
 * @method static Inspector quality()
 * @method static Glossary glossary()
 * @method static Insights insights()
 * @method static RevisionRollback revisions()
 * @method static ReviewFlow review()
 * @method static array scanUsage(string|null $path = null)
 * @method static array scanLoose(string|null $path = null)
 *
 * @see TranslationManager
 */
class Translations extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TranslationManager::class;
    }
}
