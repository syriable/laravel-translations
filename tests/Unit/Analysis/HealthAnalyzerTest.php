<?php

declare(strict_types=1);

use Syriable\Translations\Analysis\HealthAnalyzer;
use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Domain\ExtractedKey;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;
use Syriable\Translations\Domain\TranslationKey;
use Syriable\Translations\Extraction\ExtractionResult;

function catalogWith(array $locales): Catalog
{
    $built = [];

    foreach ($locales as $code => $entries) {
        $built[$code] = new LocaleCatalog(new Locale($code), $entries);
    }

    return (new Catalog($built))->withSource(array_key_first($locales));
}

function extraction(array $keys): ExtractionResult
{
    $map = [];

    foreach ($keys as $key) {
        $map[$key] = new ExtractedKey(new TranslationKey($key));
    }

    return new ExtractionResult($map);
}

it('detects keys used in code but missing from the catalog', function () {
    $report = (new HealthAnalyzer)->analyze(
        extraction(['used.key', 'missing.key']),
        catalogWith(['en' => ['used.key' => 'X']]),
    );

    expect($report->missingKeys)->toContain('missing.key')
        ->and($report->missingKeys)->not->toContain('used.key');
});

it('detects unused keys defined in the catalog', function () {
    $report = (new HealthAnalyzer)->analyze(
        extraction(['used.key']),
        catalogWith(['en' => ['used.key' => 'X', 'dead.key' => 'Y']]),
    );

    expect($report->unusedKeys)->toContain('dead.key')
        ->and($report->unusedKeys)->not->toContain('used.key');
});

it('respects ignore patterns for unused keys', function () {
    $report = (new HealthAnalyzer(['validation.*']))->analyze(
        extraction([]),
        catalogWith(['en' => ['validation.required' => 'Required']]),
    );

    expect($report->unusedKeys)->not->toContain('validation.required');
});

it('computes per-locale completeness against the source', function () {
    $report = (new HealthAnalyzer)->analyze(
        extraction([]),
        catalogWith([
            'en' => ['a' => 'A', 'b' => 'B'],
            'fr' => ['a' => 'Aa', 'b' => null],
        ]),
    );

    expect($report->completeness['fr']->percentage())->toBe(50.0)
        ->and($report->completeness['fr']->missingKeys)->toContain('b')
        ->and($report->completeness['en']->isComplete())->toBeTrue();
});
