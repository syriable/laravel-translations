<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Syriable\Translations\Events\TranslationForgotten;
use Syriable\Translations\Events\TranslationSaved;
use Syriable\Translations\Management\CatalogManager;
use Syriable\Translations\Support\Actor;

beforeEach(function () {
    $this->langDir = sys_get_temp_dir().'/syriable-catalog-'.uniqid();
    (new Filesystem)->copyDirectory(fixturePath('lang'), $this->langDir);

    config()->set('translations.lang_path', $this->langDir);
    config()->set('translations.storage.drivers.file.path', $this->langDir);

    Event::fake();

    $this->manager = app(CatalogManager::class);
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->langDir);
    Actor::resolveUsing(null);
});

it('writes a new translation to disk and reports it as created', function () {
    $this->manager->set('en', 'buttons.save', 'Save');

    expect($this->manager->get('en', 'buttons.save'))->toBe('Save')
        ->and(file_exists($this->langDir.'/en/buttons.php'))->toBeTrue();

    Event::assertDispatched(TranslationSaved::class, function (TranslationSaved $event): bool {
        return $event->locale === 'en'
            && $event->key === 'buttons.save'
            && $event->value === 'Save'
            && $event->previousValue === null
            && $event->created === true;
    });
});

it('records the previous value when updating an existing translation', function () {
    $this->manager->set('en', 'messages.welcome', 'Welcome back');

    Event::assertDispatched(TranslationSaved::class, function (TranslationSaved $event): bool {
        return $event->created === false
            && $event->previousValue === 'Welcome'
            && $event->value === 'Welcome back';
    });
});

it('does not write or dispatch when the value is unchanged', function () {
    $this->manager->set('en', 'messages.welcome', 'Welcome');

    Event::assertNotDispatched(TranslationSaved::class);
});

it('forgets a key and dispatches the event with its previous value', function () {
    $this->manager->forget('en', 'messages.welcome');

    expect($this->manager->get('en', 'messages.welcome'))->toBeNull();

    Event::assertDispatched(TranslationForgotten::class, function (TranslationForgotten $event): bool {
        return $event->key === 'messages.welcome' && $event->previousValue === 'Welcome';
    });
});

it('attributes changes to the resolved actor', function () {
    Actor::resolveUsing(fn (): string => 'editor-7');

    $this->manager->set('en', 'buttons.cancel', 'Cancel');

    Event::assertDispatched(TranslationSaved::class, fn (TranslationSaved $event): bool => $event->actor === 'editor-7');
});
