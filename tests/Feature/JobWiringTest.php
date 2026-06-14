<?php

use Illuminate\Support\Facades\Queue;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Jobs\ScanLooseJob;
use Syriable\Translations\Jobs\ScanQualityJob;
use Syriable\Translations\Jobs\ScanUsageJob;
use Syriable\Translations\Jobs\TranslateLocaleJob;
use Syriable\Translations\Models\Locale;

it('dispatches the usage scan after import when enabled', function (): void {
    Queue::fake();
    config()->set('translations.scanning.scan_after_import', true);
    makeLangFiles(['en/auth.php' => ['failed' => 'Failed']]);

    Translations::import();

    Queue::assertPushed(ScanUsageJob::class);
});

it('does not dispatch the usage scan after import by default', function (): void {
    Queue::fake();
    makeLangFiles(['en/auth.php' => ['failed' => 'Failed']]);

    Translations::import();

    Queue::assertNotPushed(ScanUsageJob::class);
});

it('queues the quality scan from translations:validate --queue', function (): void {
    Queue::fake();

    $this->artisan('translations:validate', ['--queue' => true])->assertSuccessful();

    Queue::assertPushed(ScanQualityJob::class);
});

it('queues the loose scan from translations:scan-loose --queue', function (): void {
    Queue::fake();

    $this->artisan('translations:scan-loose', ['--queue' => true])->assertSuccessful();

    Queue::assertPushed(ScanLooseJob::class);
});

it('queues a whole-locale translation from translations:translate --queue', function (): void {
    Queue::fake();
    config()->set('translations.ai.enabled', true);
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();

    $this->artisan('translations:translate', ['locale' => 'es', '--queue' => true])->assertSuccessful();

    Queue::assertPushed(TranslateLocaleJob::class);
});
