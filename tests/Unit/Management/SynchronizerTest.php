<?php

declare(strict_types=1);

use Syriable\Translations\Domain\ExtractedKey;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;
use Syriable\Translations\Domain\TranslationKey;
use Syriable\Translations\Extraction\ExtractionResult;
use Syriable\Translations\Management\Synchronizer;
use Syriable\Translations\Tests\Support\InMemoryDriver;

function extractionOf(string ...$keys): ExtractionResult
{
    $map = [];

    foreach ($keys as $key) {
        $map[$key] = new ExtractedKey(new TranslationKey($key));
    }

    return new ExtractionResult($map);
}

it('fills keys missing from source and target locales', function () {
    $driver = new InMemoryDriver([
        'en' => ['messages.welcome' => 'Welcome'],
        'fr' => ['messages.welcome' => 'Bienvenue'],
    ]);

    $report = (new Synchronizer($driver, 'en', ['fill_missing' => true]))
        ->sync(extractionOf('messages.welcome', 'buttons.save'));

    expect($report->addedFor('en'))->toContain('buttons.save')
        ->and($driver->read(new Locale('fr'))->has('buttons.save'))->toBeTrue()
        ->and($driver->read(new Locale('fr'))->get('buttons.save'))->toBeNull();
});

it('prunes unused keys when enabled', function () {
    $driver = new InMemoryDriver([
        'en' => ['used.key' => 'X', 'dead.key' => 'Y'],
    ]);

    $report = (new Synchronizer($driver, 'en', ['fill_missing' => true, 'prune_unused' => true]))
        ->sync(extractionOf('used.key'));

    expect($report->prunedFor('en'))->toContain('dead.key')
        ->and($driver->read(new Locale('en'))->has('dead.key'))->toBeFalse();
});

it('never writes when running a dry run', function () {
    $driver = new InMemoryDriver(['en' => []]);

    $report = (new Synchronizer($driver, 'en'))->sync(extractionOf('a.b'), dryRun: true);

    expect($report->dryRun)->toBeTrue()
        ->and($report->addedFor('en'))->toContain('a.b')
        ->and($driver->read(new Locale('en'))->has('a.b'))->toBeFalse();
});

it('can target a single locale', function () {
    $driver = new InMemoryDriver([
        'en' => ['a' => 'A'],
        'fr' => [],
        'de' => [],
    ]);

    (new Synchronizer($driver, 'en'))->sync(extractionOf('a'), onlyLocale: 'fr');

    expect($driver->read(new Locale('fr'))->has('a'))->toBeTrue()
        ->and($driver->read(new Locale('de'))->has('a'))->toBeFalse();
});
