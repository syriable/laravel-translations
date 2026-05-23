<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Syriable\Translations\Management\CatalogManager;
use Syriable\Translations\Models\ActivityLog;
use Syriable\Translations\Support\Actor;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->langDir = sys_get_temp_dir().'/syriable-activity-'.uniqid();
    (new Filesystem)->copyDirectory(fixturePath('lang'), $this->langDir);

    config()->set('translations.lang_path', $this->langDir);
    config()->set('translations.storage.drivers.file.path', $this->langDir);

    $this->manager = app(CatalogManager::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->langDir);
    Actor::resolveUsing(null);
});

it('records a created translation in the activity log', function () {
    Actor::resolveUsing(fn (): string => 'editor-1');

    $this->manager->set('en', 'buttons.save', 'Save');

    $log = ActivityLog::query()->sole();

    expect($log->action)->toBe('translation.created')
        ->and($log->user_id)->toBe('editor-1')
        ->and($log->locale)->toBe('en')
        ->and($log->translation_key)->toBe('buttons.save')
        ->and($log->metadata['value'])->toBe('Save');
});

it('records an update with the previous value', function () {
    $this->manager->set('en', 'messages.welcome', 'Welcome back');

    $log = ActivityLog::query()->where('action', 'translation.updated')->sole();

    expect($log->metadata['previous'])->toBe('Welcome')
        ->and($log->metadata['value'])->toBe('Welcome back');
});

it('records a forgotten translation', function () {
    $this->manager->forget('en', 'messages.welcome');

    expect(ActivityLog::query()->where('action', 'translation.forgotten')->exists())->toBeTrue();
});

it('does not log when metadata is disabled', function () {
    config()->set('translations.metadata.enabled', false);

    $this->manager->set('en', 'buttons.cancel', 'Cancel');

    expect(ActivityLog::query()->count())->toBe(0);
});
