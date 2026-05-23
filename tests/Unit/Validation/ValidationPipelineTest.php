<?php

declare(strict_types=1);

use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;
use Syriable\Translations\Validation\Rules\HtmlTagRule;
use Syriable\Translations\Validation\Rules\PlaceholderConsistencyRule;
use Syriable\Translations\Validation\Rules\PluralFormRule;
use Syriable\Translations\Validation\ValidationPipeline;

function pipeline(): ValidationPipeline
{
    return new ValidationPipeline(
        [new PlaceholderConsistencyRule, new PluralFormRule, new HtmlTagRule],
        'en',
    );
}

function catalog(array $en, array $fr): Catalog
{
    return (new Catalog([
        'en' => new LocaleCatalog(new Locale('en'), $en),
        'fr' => new LocaleCatalog(new Locale('fr'), $fr),
    ]))->withSource('en');
}

it('flags a missing placeholder as an error', function () {
    $report = pipeline()->validate(catalog(
        ['greeting' => 'Hello :name'],
        ['greeting' => 'Bonjour'],
    ));

    expect($report->hasErrors())->toBeTrue()
        ->and($report->errors()[0]->rule)->toBe('placeholder_consistency');
});

it('passes when placeholders match', function () {
    $report = pipeline()->validate(catalog(
        ['greeting' => 'Hello :name'],
        ['greeting' => 'Bonjour :name'],
    ));

    expect($report->isEmpty())->toBeTrue();
});

it('flags a pluralized translation that lacks its language plural forms', function () {
    // French uses two plural forms; a single segment is missing one.
    $report = pipeline()->validate(catalog(
        ['apples' => 'one apple|many apples'],
        ['apples' => 'une pomme'],
    ));

    expect($report->count())->toBe(1)
        ->and($report->issues[0]->rule)->toBe('plural_form');
});

it('flags differing html tags', function () {
    $report = pipeline()->validate(catalog(
        ['terms' => 'Accept the <a>terms</a>'],
        ['terms' => 'Accepter les termes'],
    ));

    expect($report->issues[0]->rule)->toBe('html_tags');
});

it('skips untranslated target values', function () {
    $report = pipeline()->validate(catalog(
        ['greeting' => 'Hello :name'],
        ['greeting' => null],
    ));

    expect($report->isEmpty())->toBeTrue();
});
