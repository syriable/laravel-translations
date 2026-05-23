<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Syriable\Translations\Management\CatalogManager;
use Syriable\Translations\Models\ValidationIssue;

beforeEach(function () {
    $this->langDir = sys_get_temp_dir().'/syriable-validation-'.uniqid();
    (new Filesystem)->copyDirectory(fixturePath('lang'), $this->langDir);

    config()->set('translations.lang_path', $this->langDir);
    config()->set('translations.storage.drivers.file.path', $this->langDir);
    config()->set('translations.locales.source', 'en');

    // A source value with a placeholder the targets must preserve.
    file_put_contents($this->langDir.'/en/messages.php', "<?php\n\nreturn ['greeting' => 'Hello :name'];\n");
    file_put_contents($this->langDir.'/fr/messages.php', "<?php\n\nreturn ['greeting' => 'Bonjour :name'];\n");

    $this->manager = app(CatalogManager::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->langDir);
});

it('persists an issue when a save introduces an inconsistency', function () {
    $this->manager->set('fr', 'messages.greeting', 'Bonjour');

    $issue = ValidationIssue::query()->forKey('fr', 'messages.greeting')->sole();

    expect($issue->check)->toBe('placeholder_consistency')
        ->and($issue->severity->value)->toBe('error')
        ->and($issue->locale)->toBe('fr');
});

it('clears the stored issue once the translation is fixed', function () {
    $this->manager->set('fr', 'messages.greeting', 'Bonjour');
    expect(ValidationIssue::query()->forKey('fr', 'messages.greeting')->exists())->toBeTrue();

    $this->manager->set('fr', 'messages.greeting', 'Bonjour :name');

    expect(ValidationIssue::query()->forKey('fr', 'messages.greeting')->exists())->toBeFalse();
});

it('does not validate edits to the source locale', function () {
    $this->manager->set('en', 'messages.greeting', 'Hello :name and :other');

    expect(ValidationIssue::query()->where('locale', 'en')->count())->toBe(0);
});

it('persists issues from the validate command', function () {
    file_put_contents($this->langDir.'/fr/messages.php', "<?php\n\nreturn ['greeting' => 'Bonjour'];\n");

    $this->artisan('translations:validate')->assertFailed();

    expect(ValidationIssue::query()->forKey('fr', 'messages.greeting')->exists())->toBeTrue();
});

it('does not persist when metadata is disabled', function () {
    config()->set('translations.metadata.enabled', false);

    $this->manager->set('fr', 'messages.greeting', 'Bonjour');

    expect(ValidationIssue::query()->count())->toBe(0);
});
