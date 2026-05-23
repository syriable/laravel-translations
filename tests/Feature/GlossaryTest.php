<?php

declare(strict_types=1);

use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Glossary\GlossaryService;
use Syriable\Translations\Models\GlossaryTerm;
use Syriable\Translations\Validation\Rules\GlossaryConsistencyRule;

function defineTerm(string $source, string $locale, string $translation, array $attributes = []): GlossaryTerm
{
    $term = GlossaryTerm::query()->create(['source_term' => $source] + $attributes);
    $term->translations()->create(['locale' => $locale, 'translation' => $translation]);

    return $term;
}

function glossaryRule(): GlossaryConsistencyRule
{
    return new GlossaryConsistencyRule(app(GlossaryService::class));
}

function check(string $source, string $target, string $locale = 'fr'): array
{
    return glossaryRule()->validate(
        new Translation('dashboard.title', $source),
        new Translation('dashboard.title', $target),
        new Locale($locale),
    );
}

it('flags a target that does not use the agreed glossary translation', function () {
    defineTerm('Dashboard', 'fr', 'Tableau de bord');

    $issues = check('Open the Dashboard', 'Ouvrir le panneau');

    expect($issues)->toHaveCount(1)
        ->and($issues[0]->rule)->toBe('glossary_consistency')
        ->and($issues[0]->suggestion)->toBe('Tableau de bord');
});

it('passes when the target uses the agreed translation', function () {
    defineTerm('Dashboard', 'fr', 'Tableau de bord');

    expect(check('Open the Dashboard', 'Ouvrir le Tableau de bord'))->toBe([]);
});

it('ignores terms not present in the source value', function () {
    defineTerm('Dashboard', 'fr', 'Tableau de bord');

    expect(check('Open the settings', 'Ouvrir les paramètres'))->toBe([]);
});

it('respects case sensitivity', function () {
    defineTerm('iOS', 'fr', 'iOS', ['case_sensitive' => true]);

    // Lowercase "ios" should not match the case-sensitive term.
    expect(check('install ios app', 'installer app ios'))->toBe([]);
});

it('only considers terms with a translation for the locale', function () {
    defineTerm('Dashboard', 'de', 'Übersicht');

    // No French translation defined, so French validation has nothing to check.
    expect(check('Open the Dashboard', 'Ouvrir le panneau', 'fr'))->toBe([]);
});

it('returns no entries when metadata is disabled', function () {
    defineTerm('Dashboard', 'fr', 'Tableau de bord');
    config()->set('translations.metadata.enabled', false);

    expect(app(GlossaryService::class)->forLocale('fr'))->toBe([]);
});
