<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Syriable\Translations\Ai\AiTranslationService;
use Syriable\Translations\Contracts\Translator;
use Syriable\Translations\Models\AiUsageLog;
use Syriable\Translations\Models\GlossaryTerm;
use Syriable\Translations\Tests\Support\FakeTranslator;

beforeEach(function () {
    $this->langDir = sys_get_temp_dir().'/syriable-ai-'.uniqid();
    (new Filesystem)->makeDirectory($this->langDir.'/en', recursive: true);
    (new Filesystem)->makeDirectory($this->langDir.'/fr', recursive: true);

    file_put_contents($this->langDir.'/en/messages.php', "<?php\n\nreturn ['welcome' => 'Welcome', 'bye' => 'Goodbye'];\n");
    file_put_contents($this->langDir.'/fr/messages.php', "<?php\n\nreturn ['welcome' => 'Bienvenue'];\n");

    config()->set('translations.lang_path', $this->langDir);
    config()->set('translations.storage.drivers.file.path', $this->langDir);
    config()->set('translations.locales.source', 'en');
    config()->set('translations.ai.enabled', true);

    $this->fake = new FakeTranslator;
    app()->instance(Translator::class, $this->fake);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->langDir);
});

it('fills only the missing target values', function () {
    $result = app(AiTranslationService::class)->translateMissing('fr');

    expect($result->translated)->toBe(1)
        ->and(app(AiTranslationService::class)->available())->toBeTrue();

    $fr = app('files')->getRequire($this->langDir.'/fr/messages.php');

    // "welcome" was already translated and must be left untouched.
    expect($fr['welcome'])->toBe('Bienvenue')
        ->and($fr['bye'])->toBe('[fr] Goodbye');
});

it('logs provider usage for the run', function () {
    app(AiTranslationService::class)->translateMissing('fr');

    $log = AiUsageLog::query()->sole();

    expect($log->provider)->toBe('fake')
        ->and($log->model)->toBe('fake-1')
        ->and($log->target_locale)->toBe('fr')
        ->and($log->keys)->toBe(1)
        ->and($log->output_characters)->toBeGreaterThan(0);
});

it('passes glossary terms to the translator', function () {
    $term = GlossaryTerm::query()->create(['source_term' => 'Goodbye']);
    $term->translations()->create(['locale' => 'fr', 'translation' => 'Au revoir']);

    app(AiTranslationService::class)->translateMissing('fr');

    $fr = app('files')->getRequire($this->langDir.'/fr/messages.php');

    expect($this->fake->receivedGlossary)->toHaveCount(1)
        ->and($fr['bye'])->toBe('[fr] Au revoir');
});

it('runs through the translate command', function () {
    $this->artisan('translations:translate', ['locale' => 'fr'])
        ->expectsOutputToContain('Translated 1 key')
        ->assertSuccessful();
});

it('reports unavailable when ai is disabled', function () {
    config()->set('translations.ai.enabled', false);

    expect(app(AiTranslationService::class)->available())->toBeFalse();

    $this->artisan('translations:translate', ['locale' => 'fr'])->assertFailed();
});

it('flags AI output as AI-generated and needing review when the workflow is on', function () {
    config()->set('translations.workflow.enabled', true);

    app(AiTranslationService::class)->translateMissing('fr');

    $state = app(Syriable\Translations\Workflow\WorkflowService::class)->statusFor('fr', 'messages.bye');

    expect($state->status)->toBe(Syriable\Translations\Domain\Enums\ReviewStatus::NeedsReview)
        ->and($state->ai_generated)->toBeTrue();
});

it('still writes translations but logs nothing when metadata is disabled', function () {
    config()->set('translations.metadata.enabled', false);

    app(AiTranslationService::class)->translateMissing('fr');

    $fr = app('files')->getRequire($this->langDir.'/fr/messages.php');

    expect($fr['bye'])->toBe('[fr] Goodbye')
        ->and(AiUsageLog::query()->count())->toBe(0);
});
