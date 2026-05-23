<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Syriable\Translations\Domain\Enums\ReviewStatus;
use Syriable\Translations\Management\CatalogManager;
use Syriable\Translations\Models\ActivityLog;
use Syriable\Translations\Models\TranslationState;
use Syriable\Translations\Workflow\WorkflowService;

beforeEach(function () {
    $this->langDir = sys_get_temp_dir().'/syriable-workflow-'.uniqid();
    (new Filesystem)->copyDirectory(fixturePath('lang'), $this->langDir);

    config()->set('translations.lang_path', $this->langDir);
    config()->set('translations.storage.drivers.file.path', $this->langDir);
    config()->set('translations.locales.source', 'en');
    config()->set('translations.workflow.enabled', true);

    $this->manager = app(CatalogManager::class);
    $this->workflow = app(WorkflowService::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->langDir);
});

it('flags an edited translation for review', function () {
    $this->manager->set('fr', 'messages.welcome', 'Bienvenue !');

    $state = $this->workflow->statusFor('fr', 'messages.welcome');

    expect($state->status)->toBe(ReviewStatus::NeedsReview)
        ->and($state->ai_generated)->toBeFalse();
});

it('does not flag edits to the source locale', function () {
    $this->manager->set('en', 'messages.welcome', 'Welcome!');

    expect($this->workflow->statusFor('en', 'messages.welcome'))->toBeNull();
});

it('approves a translation and records the reviewer', function () {
    $this->manager->set('fr', 'messages.welcome', 'Bienvenue !');

    $this->workflow->approve('fr', 'messages.welcome', 'reviewer-9');

    $state = $this->workflow->statusFor('fr', 'messages.welcome');

    expect($state->status)->toBe(ReviewStatus::Approved)
        ->and($state->reviewed_by)->toBe('reviewer-9')
        ->and(ActivityLog::query()->where('action', 'translation.approved')->exists())->toBeTrue();
});

it('rejects a translation with feedback', function () {
    $this->manager->set('fr', 'messages.welcome', 'Bienvenue !');

    $this->workflow->reject('fr', 'messages.welcome', 'Too informal', 'reviewer-9');

    $state = $this->workflow->statusFor('fr', 'messages.welcome');

    expect($state->status)->toBe(ReviewStatus::Rejected)
        ->and($state->reviewer_feedback)->toBe('Too informal');
});

it('returns an approved translation to review when edited again', function () {
    $this->manager->set('fr', 'messages.welcome', 'Bienvenue !');
    $this->workflow->approve('fr', 'messages.welcome', 'reviewer-9');

    $this->manager->set('fr', 'messages.welcome', 'Salut !');

    $state = $this->workflow->statusFor('fr', 'messages.welcome');

    expect($state->status)->toBe(ReviewStatus::NeedsReview)
        ->and($state->reviewed_by)->toBeNull();
});

it('does not track state when the workflow is disabled', function () {
    config()->set('translations.workflow.enabled', false);

    $this->manager->set('fr', 'messages.welcome', 'Bienvenue !');

    expect(TranslationState::query()->count())->toBe(0);
});

it('fails the review command in strict mode when items are pending', function () {
    $this->manager->set('fr', 'messages.welcome', 'Bienvenue !');

    $this->artisan('translations:review --strict')->assertFailed();

    $this->workflow->approve('fr', 'messages.welcome', 'reviewer-9');

    $this->artisan('translations:review --strict')->assertSuccessful();
});
