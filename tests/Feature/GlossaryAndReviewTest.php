<?php

use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

it('stores and matches glossary terms for a locale', function (): void {
    $term = Translations::glossary()->define('invoice', note: 'billing document');
    $es = Locale::query()->where('code', 'es')->first();
    Translations::glossary()->translate($term, $es->id, 'factura');

    $pairs = Translations::glossary()->pairsFor('Download your invoice now', $es->id);

    expect($pairs)->toBe(['invoice' => 'factura']);
});

it('moves non-reviewer saves into pending review when the workflow is enabled', function (): void {
    config()->set('translations.review.enabled', true);

    $status = Translations::review()->statusForSave(Syriable\Translations\Enums\MemberRole::Translator);
    expect($status)->toBe(MessageStatus::PendingReview);

    $reviewerStatus = Translations::review()->statusForSave(Syriable\Translations\Enums\MemberRole::Reviewer);
    expect($reviewerStatus)->toBe(MessageStatus::Approved);
});

it('approves and rejects a translation', function (): void {
    $message = Translations::set('messages.x', 'Hola', 'es');

    Translations::review()->approve($message, 'reviewer-1');
    expect($message->fresh()->status)->toBe(MessageStatus::Approved);

    Translations::review()->reject($message, 'Too informal', 'reviewer-1');
    $fresh = $message->fresh();
    expect($fresh->status)->toBe(MessageStatus::PendingReview);
    expect($fresh->review_note)->toBe('Too informal');
});
