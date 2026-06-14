<?php

use Syriable\Translations\Ai\FakeTranslator;
use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Jobs\ScanLooseJob;
use Syriable\Translations\Jobs\ScanQualityJob;
use Syriable\Translations\Jobs\ScanUsageJob;
use Syriable\Translations\Jobs\TranslateLocaleJob;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\LooseString;
use Syriable\Translations\Models\Message;
use Syriable\Translations\Models\PhraseUsage;
use Syriable\Translations\Models\QualityIssue;

function jobWorkspace(string $file, string $contents): string
{
    $dir = sys_get_temp_dir().'/syriable-jobs/'.uniqid();
    @mkdir($dir, 0755, true);
    file_put_contents($dir.'/'.$file, $contents);

    return $dir;
}

it('records phrase usages when the usage job handles', function (): void {
    Translations::set('messages.greeting', 'Hello', 'en');
    $dir = jobWorkspace('home.blade.php', "<h1>{{ __('messages.greeting') }}</h1>");

    ScanUsageJob::dispatchSync($dir);

    expect(PhraseUsage::query()->count())->toBeGreaterThan(0);
});

it('records hardcoded strings when the loose job handles', function (): void {
    $dir = jobWorkspace('page.blade.php', '<h1>Welcome to the dashboard</h1>');

    ScanLooseJob::dispatchSync($dir);

    expect(LooseString::query()->where('text', 'Welcome to the dashboard')->exists())->toBeTrue();
});

it('persists quality issues when the quality job handles', function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
    config()->set('translations.quality.run_on_save', false);

    Translations::set('m.welcome', 'Hello :name', 'en');
    Translations::set('m.welcome', 'Hola', 'es');
    expect(QualityIssue::query()->count())->toBe(0);

    ScanQualityJob::dispatchSync();

    expect(QualityIssue::query()->where('check', 'missing_placeholder')->exists())->toBeTrue();
});

it('translates open messages when the translate job handles', function (): void {
    $this->app->instance(Translator::class, new FakeTranslator(fn ($request) => 'TR:'.$request->text));
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();

    Translations::set('m.a', 'Alpha', 'en');
    $es = Locale::query()->where('code', 'es')->first();

    TranslateLocaleJob::dispatchSync($es->id);

    expect(Message::query()->where('locale_id', $es->id)->open()->count())->toBe(0);
    expect(Translations::get('m.a', 'es'))->toBe('TR:Alpha');
});
