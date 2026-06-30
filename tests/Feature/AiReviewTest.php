<?php

use Syriable\Translations\Ai\FakeReviewer;
use Syriable\Translations\Contracts\Reviewer;
use Syriable\Translations\Enums\Severity;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\AiUsage;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Support\ReviewIssue;
use Syriable\Translations\Support\ReviewRequest;

beforeEach(function (): void {
    $this->fake = new FakeReviewer(fn (ReviewRequest $request) => [
        new ReviewIssue('messages.greeting', Severity::Warning, 'Unnatural phrasing.', 'Use a warmer greeting.'),
    ]);
    $this->app->instance(Reviewer::class, $this->fake);

    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

function spanish(): Locale
{
    return Locale::query()->where('code', 'es')->firstOrFail();
}

it('reviews translated messages and reports issues, logging usage', function (): void {
    Translations::set('messages.greeting', 'Hello there', 'en');
    Translations::set('messages.greeting', 'Hola', 'es');

    $result = Translations::aiReview()->review(spanish());

    expect($result->hasIssues())->toBeTrue();
    expect($result->issues[0]->key)->toBe('messages.greeting');
    expect($result->issues[0]->severity)->toBe(Severity::Warning);
    expect($result->countsBySeverity())->toBe(['error' => 0, 'warning' => 1, 'info' => 0]);
    expect(AiUsage::query()->where('success', true)->whereNull('phrase_id')->count())->toBe(1);
});

it('forwards the source/target pairs into the review request', function (): void {
    Translations::set('messages.greeting', 'Hello there', 'en');
    Translations::set('messages.greeting', 'Hola', 'es');

    Translations::aiReview()->review(spanish());

    expect($this->fake->requests[0]->pairs)->toBe([
        'messages.greeting' => ['source' => 'Hello there', 'target' => 'Hola'],
    ]);
    expect($this->fake->requests[0]->sourceLocale)->toBe('en');
    expect($this->fake->requests[0]->targetLocale)->toBe('es');
});

it('only reviews translated messages, skipping untranslated ones', function (): void {
    Translations::set('messages.done', 'Done', 'en');
    Translations::set('messages.done', 'Hecho', 'es');
    Translations::set('messages.pending', 'Pending', 'en');

    Translations::aiReview()->review(spanish());

    expect(array_keys($this->fake->requests[0]->pairs))->toBe(['messages.done']);
});

it('returns an empty result when the reviewer finds nothing', function (): void {
    $this->app->instance(Reviewer::class, new FakeReviewer);

    Translations::set('messages.greeting', 'Hello there', 'en');
    Translations::set('messages.greeting', 'Hola', 'es');

    $result = Translations::aiReview()->review(spanish());

    expect($result->hasIssues())->toBeFalse();
    expect($result->issues)->toBe([]);
});

it('drops a requested provider that is not allowlisted', function (): void {
    config()->set('translations.ai.allowed_providers', ['openai']);

    Translations::set('messages.greeting', 'Hello there', 'en');
    Translations::set('messages.greeting', 'Hola', 'es');

    Translations::aiReview()->review(spanish(), ['provider' => 'bogus']);

    expect($this->fake->requests[0]->provider)->toBeNull();
});

it('reviews each batch separately when there are more pairs than the batch size', function (): void {
    config()->set('translations.ai.review.batch_size', 1);

    Translations::set('messages.a', 'Alpha', 'en');
    Translations::set('messages.a', 'Alfa', 'es');
    Translations::set('messages.b', 'Beta', 'en');
    Translations::set('messages.b', 'Beta', 'es');

    Translations::aiReview()->review(spanish());

    expect($this->fake->requests)->toHaveCount(2);
    expect(AiUsage::query()->whereNull('phrase_id')->count())->toBe(2);
});

it('runs the ai-review command and reports a clean result', function (): void {
    config()->set('translations.ai.enabled', true);
    $this->app->instance(Reviewer::class, new FakeReviewer);

    Translations::set('messages.greeting', 'Hello there', 'en');
    Translations::set('messages.greeting', 'Hola', 'es');

    $this->artisan('translations:ai-review', ['locale' => 'es'])->assertSuccessful();
});

it('fails the ai-review command when a high-severity issue is found', function (): void {
    config()->set('translations.ai.enabled', true);
    $this->app->instance(Reviewer::class, new FakeReviewer(fn () => [
        new ReviewIssue('messages.greeting', Severity::Error, 'Placeholder dropped.', null),
    ]));

    Translations::set('messages.greeting', 'Hello :name', 'en');
    Translations::set('messages.greeting', 'Hola', 'es');

    $this->artisan('translations:ai-review', ['locale' => 'es'])->assertFailed();
});

it('refuses to run the command when AI is disabled', function (): void {
    config()->set('translations.ai.enabled', false);

    $this->artisan('translations:ai-review', ['locale' => 'es'])->assertFailed();
});
