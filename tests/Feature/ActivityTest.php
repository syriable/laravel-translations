<?php

use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Activity;
use Syriable\Translations\Models\Locale;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

it('records an activity when a message status changes', function (): void {
    $message = Translations::set('messages.greeting', 'Hola', 'es');

    Translations::review()->approve($message, 'reviewer-1');

    $activity = Activity::query()->where('action', 'status_changed')->latest('id')->first();

    expect($activity)->not->toBeNull();
    expect($activity->member_id)->toBe('reviewer-1');
    expect($activity->subject_type)->toBe($message::class);
    expect($activity->subject_id)->toBe((string) $message->id);
    expect($activity->meta['to'])->toBe('approved');
});

it('records a review_requested activity when a review is requested', function (): void {
    $message = Translations::set('messages.greeting', 'Hola', 'es');
    $message->update(['status' => MessageStatus::Draft]);

    Translations::review()->requestReview($message, 'translator-1');

    $activity = Activity::query()->where('action', 'review_requested')->latest('id')->first();

    expect($activity)->not->toBeNull();
    expect($activity->member_id)->toBe('translator-1');
    expect($activity->meta['from'])->toBe('draft');
    expect($activity->meta['to'])->toBe('pending_review');
});

it('records an activity when a comment is posted on a message', function (): void {
    $message = Translations::set('messages.greeting', 'Hola', 'es');

    $comment = $message->comment('Please double check this wording', 'reviewer-1');

    $activity = Activity::query()->where('action', 'comment_added')->latest('id')->first();

    expect($activity)->not->toBeNull();
    expect($activity->member_id)->toBe('reviewer-1');
    expect($activity->meta['comment_id'])->toBe($comment->id);
    expect($activity->meta['body'])->toBe('Please double check this wording');

    expect($message->comments()->count())->toBe(1);
    expect($message->activities()->where('action', 'comment_added')->count())->toBe(1);
});

it('does not record activities when disabled', function (): void {
    config()->set('translations.activities.enabled', false);

    $message = Translations::set('messages.greeting', 'Hola', 'es');
    Translations::review()->approve($message, 'reviewer-1');
    $message->comment('note', 'reviewer-1');

    expect(Activity::query()->count())->toBe(0);
});
