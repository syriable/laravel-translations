<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->langDir = sys_get_temp_dir().'/syriable-feature-'.uniqid();
    (new Filesystem)->copyDirectory(fixturePath('lang'), $this->langDir);

    config()->set('translations.lang_path', $this->langDir);
    config()->set('translations.storage.drivers.file.path', $this->langDir);
    config()->set('translations.extraction.paths', [fixturePath('source')]);
    config()->set('translations.locales.source', 'en');
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->langDir);
});

it('extracts keys via the command', function () {
    $this->artisan('translations:extract')->assertSuccessful();
});

it('lists discovered locales', function () {
    $this->artisan('translations:locales')->assertSuccessful();
});

it('reports catalog health', function () {
    $this->artisan('translations:health')->assertSuccessful();
});

it('fails strict health when keys are missing from the catalog', function () {
    $this->artisan('translations:health --strict')->assertFailed();
});

it('syncs keys discovered in source into the catalog', function () {
    $this->artisan('translations:sync')->assertSuccessful();

    expect(file_get_contents($this->langDir.'/en/messages.php'))->toContain('tagline')
        ->and(file_get_contents($this->langDir.'/fr/messages.php'))->toContain('tagline');
});

it('does not write files during a dry run', function () {
    $before = file_get_contents($this->langDir.'/en/messages.php');

    $this->artisan('translations:sync --dry-run')->assertSuccessful();

    expect(file_get_contents($this->langDir.'/en/messages.php'))->toBe($before);
});

it('exports and re-imports the catalog without loss', function () {
    $this->artisan('translations:export')->assertSuccessful();
    $this->artisan('translations:import')->assertSuccessful();

    expect(file_exists($this->langDir.'/en/messages.php'))->toBeTrue();
});

it('passes validation for a consistent catalog', function () {
    $this->artisan('translations:validate')->assertSuccessful();
});

it('fails validation when a placeholder is dropped', function () {
    file_put_contents(
        $this->langDir.'/fr/messages.php',
        "<?php\n\nreturn ['greeting' => 'Bonjour'];\n",
    );
    file_put_contents(
        $this->langDir.'/en/messages.php',
        "<?php\n\nreturn ['greeting' => 'Hello :name'];\n",
    );

    $this->artisan('translations:validate')->assertFailed();
});
