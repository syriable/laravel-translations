<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Syriable\Translations\Analysis\AnalyticsService;
use Syriable\Translations\Management\CatalogManager;

beforeEach(function () {
    $this->langDir = sys_get_temp_dir().'/syriable-analytics-'.uniqid();
    (new Filesystem)->makeDirectory($this->langDir.'/en', recursive: true);
    (new Filesystem)->makeDirectory($this->langDir.'/fr', recursive: true);

    file_put_contents($this->langDir.'/en/messages.php', "<?php\n\nreturn ['a' => 'A', 'b' => 'B'];\n");
    file_put_contents($this->langDir.'/fr/messages.php', "<?php\n\nreturn ['a' => 'Ay'];\n");

    config()->set('translations.lang_path', $this->langDir);
    config()->set('translations.storage.drivers.file.path', $this->langDir);
    config()->set('translations.locales.source', 'en');

    $this->analytics = app(AnalyticsService::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->langDir);
});

it('reports per-locale completeness against the source', function () {
    $reports = collect($this->analytics->completeness())->keyBy('locale');

    expect($reports['en']->percentage())->toBe(100.0)
        ->and($reports['fr']->translated)->toBe(1)
        ->and($reports['fr']->total)->toBe(2)
        ->and($reports['fr']->percentage())->toBe(50.0);
});

it('aggregates collaboration metadata into the overview', function () {
    config()->set('translations.workflow.enabled', true);

    // One edit flags a pending review and logs activity.
    app(CatalogManager::class)->set('fr', 'messages.b', 'Bee');

    $overview = $this->analytics->overview();

    expect($overview->totalKeys)->toBe(2)
        ->and($overview->pendingReviews)->toBe(1)
        ->and($overview->activityEvents)->toBeGreaterThan(0);
});

it('counts validation issues by severity', function () {
    // Source has a placeholder the French value drops -> one error.
    file_put_contents($this->langDir.'/en/messages.php', "<?php\n\nreturn ['a' => 'Hi :name'];\n");
    file_put_contents($this->langDir.'/fr/messages.php', "<?php\n\nreturn ['a' => 'Salut'];\n");

    $this->artisan('translations:validate')->assertFailed();

    expect($this->analytics->overview()->issuesBySeverity)->toHaveKey('error');
});

it('zeroes metadata figures in pure file mode', function () {
    config()->set('translations.metadata.enabled', false);
    config()->set('translations.workflow.enabled', true);

    app(CatalogManager::class)->set('fr', 'messages.b', 'Bee');

    $overview = $this->analytics->overview();

    expect($overview->pendingReviews)->toBe(0)
        ->and($overview->activityEvents)->toBe(0)
        ->and($overview->issuesBySeverity)->toBe([]);
});

it('runs the stats command', function () {
    $this->artisan('translations:stats')
        ->expectsOutputToContain('Source keys: 2')
        ->assertSuccessful();
});
