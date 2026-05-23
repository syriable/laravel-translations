<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Syriable\Translations\Domain\Enums\RevisionType;
use Syriable\Translations\Management\CatalogManager;
use Syriable\Translations\Models\TranslationRevision;
use Syriable\Translations\Support\Actor;

beforeEach(function () {
    $this->langDir = sys_get_temp_dir().'/syriable-revision-'.uniqid();
    (new Filesystem)->copyDirectory(fixturePath('lang'), $this->langDir);

    config()->set('translations.lang_path', $this->langDir);
    config()->set('translations.storage.drivers.file.path', $this->langDir);

    $this->manager = app(CatalogManager::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->langDir);
    Actor::resolveUsing(null);
});

it('records a created revision with no old value', function () {
    Actor::resolveUsing(fn (): string => 'editor-1');

    $this->manager->set('en', 'buttons.save', 'Save');

    $revision = TranslationRevision::query()->sole();

    expect($revision->change_type)->toBe(RevisionType::Created)
        ->and($revision->old_value)->toBeNull()
        ->and($revision->new_value)->toBe('Save')
        ->and($revision->changed_by)->toBe('editor-1')
        ->and($revision->locale)->toBe('en')
        ->and($revision->translation_key)->toBe('buttons.save');
});

it('records an updated revision capturing the old and new value', function () {
    $this->manager->set('en', 'messages.welcome', 'Welcome back');

    $revision = TranslationRevision::query()->where('change_type', RevisionType::Updated)->sole();

    expect($revision->old_value)->toBe('Welcome')
        ->and($revision->new_value)->toBe('Welcome back');
});

it('records a deleted revision when a key is forgotten', function () {
    $this->manager->forget('en', 'messages.welcome');

    $revision = TranslationRevision::query()->sole();

    expect($revision->change_type)->toBe(RevisionType::Deleted)
        ->and($revision->old_value)->toBe('Welcome')
        ->and($revision->new_value)->toBeNull();
});

it('builds a per-key history queryable by locale and key', function () {
    $this->manager->set('en', 'messages.welcome', 'Hi');
    $this->manager->set('en', 'messages.welcome', 'Hello');
    $this->manager->set('fr', 'messages.welcome', 'Salut');

    $history = TranslationRevision::query()->forKey('en', 'messages.welcome')->get();

    expect($history)->toHaveCount(2)
        ->and(TranslationRevision::query()->count())->toBe(3);
});

it('does not record revisions when metadata is disabled', function () {
    config()->set('translations.metadata.enabled', false);

    $this->manager->set('en', 'buttons.cancel', 'Cancel');

    expect(TranslationRevision::query()->count())->toBe(0);
});
