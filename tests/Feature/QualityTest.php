<?php

use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\QualityIssue;
use Syriable\Translations\Quality\Inspector;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

it('flags a missing placeholder as an error', function (): void {
    Translations::set('messages.welcome', 'Hello :name, you have :count messages', 'en');
    Translations::set('messages.welcome', 'Hola, tienes :count mensajes', 'es');

    $issue = QualityIssue::query()->where('check', 'missing_placeholder')->first();

    expect($issue)->not->toBeNull();
    expect($issue->severity->value)->toBe('error');
    expect($issue->meta['missing'])->toContain(':name');
});

it('flags an html tag mismatch', function (): void {
    Translations::set('messages.terms', 'Read the <a>terms</a>', 'en');
    Translations::set('messages.terms', 'Lee los terminos', 'es');

    expect(QualityIssue::query()->where('check', 'html_tag_mismatch')->exists())->toBeTrue();
});

it('auto-fixes whitespace and casing issues', function (): void {
    config()->set('translations.quality.run_on_save', false);

    Translations::set('messages.label', 'Save changes', 'en');
    $message = Translations::set('messages.label', '  save changes  ', 'es');

    $inspector = app(Inspector::class);
    $inspector->inspectAndStore($message->fresh(['locale']));

    foreach (QualityIssue::query()->where('fixable', true)->get() as $issue) {
        $inspector->fix($issue);
    }

    expect(Translations::get('messages.label', 'es'))->toBe('Save changes');
});

it('flags and fixes internal double spaces', function (): void {
    config()->set('translations.quality.run_on_save', false);

    Translations::set('messages.accept', 'The :attribute field must be accepted.', 'en');
    $message = Translations::set('messages.accept', 'يجب قبول      حقل :attribute.', 'es');

    $inspector = app(Inspector::class);
    $inspector->inspectAndStore($message->fresh(['locale']));

    $issue = QualityIssue::query()->where('check', 'whitespace')->first();

    expect($issue)->not->toBeNull();
    expect($issue->detail)->toContain('double spaces');

    $inspector->fix($issue);

    expect(Translations::get('messages.accept', 'es'))->toBe('يجب قبول حقل :attribute.');
});

it('flags a plural translation that drops its selectors', function (): void {
    config()->set('translations.quality.run_on_save', false);

    Translations::set('messages.minutes', '{1} :value minute ago|[2,*] :value minutes ago', 'en');
    $message = Translations::set('messages.minutes', ':value minute ago|:value minutes ago', 'es');

    $inspector = app(Inspector::class);
    $inspector->inspectAndStore($message->fresh(['locale']));

    $issue = QualityIssue::query()->where('check', 'plural')->first();

    expect($issue)->not->toBeNull();
    expect($issue->severity->value)->toBe('error');
});

it('flags a plural translation that uses the wrong segment separator', function (): void {
    config()->set('translations.quality.run_on_save', false);

    Translations::set('messages.minutes', '{1} :value minute ago|[2,*] :value minutes ago', 'en');
    $message = Translations::set('messages.minutes', ':value minute ago,:value minutes ago', 'es');

    $inspector = app(Inspector::class);
    $inspector->inspectAndStore($message->fresh(['locale']));

    expect(QualityIssue::query()->where('check', 'plural')->exists())->toBeTrue();
});

it('does not flag a plural translation that preserves its selectors', function (): void {
    config()->set('translations.quality.run_on_save', false);

    Translations::set('messages.minutes', '{1} :value minute ago|[2,*] :value minutes ago', 'en');
    $message = Translations::set('messages.minutes', '{1} منذ :value دقيقة|[2,*] منذ :value دقائق', 'es');

    $inspector = app(Inspector::class);
    $inspector->inspectAndStore($message->fresh(['locale']));

    expect(QualityIssue::query()->where('check', 'plural')->exists())->toBeFalse();
});

it('does not flag the source locale against itself', function (): void {
    Translations::set('messages.welcome', 'Hello :name', 'en');

    expect(QualityIssue::query()->whereHas('message', fn ($q) => $q->whereHas('locale', fn ($l) => $l->where('is_source', true)))->count())->toBe(0);
});
