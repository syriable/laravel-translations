<?php

use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\LooseString;
use Syriable\Translations\Models\PhraseUsage;
use Syriable\Translations\Scanning\Loose\LooseStringScanner;
use Syriable\Translations\Scanning\Usage\UsageScanner;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Locale::flushSourceCache();

    $this->workspace = sys_get_temp_dir().'/syriable-scan/'.uniqid();
    @mkdir($this->workspace.'/views', 0755, true);
});

it('records where a translation key is used in source code', function (): void {
    Translations::set('messages.greeting', 'Hello', 'en');

    file_put_contents($this->workspace.'/views/home.blade.php', "<h1>{{ __('messages.greeting') }}</h1>");

    $result = (new UsageScanner)->scan('views', $this->workspace);

    expect($result['usages_found'])->toBe(1);
    expect(PhraseUsage::query()->where('file_path', 'views/home.blade.php')->exists())->toBeTrue();
});

it('detects hardcoded strings while skipping translated ones', function (): void {
    file_put_contents(
        $this->workspace.'/views/page.blade.php',
        "<h1>Welcome to the dashboard</h1>\n<p>{{ __('messages.ok') }}</p>",
    );

    $result = (new LooseStringScanner)->scan('views', $this->workspace);

    expect($result['detected'])->toBe(1);
    expect(LooseString::query()->where('text', 'Welcome to the dashboard')->exists())->toBeTrue();
    expect(LooseString::query()->where('text', 'like')->exists())->toBeFalse();
});
