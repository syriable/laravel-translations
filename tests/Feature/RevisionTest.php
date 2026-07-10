<?php

use Syriable\Translations\Enums\MessageStatus;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\Revision;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

it('records a revision each time a value changes', function (): void {
    Translations::set('messages.greeting', 'Hola', 'es', ['by' => 'alice']);
    Translations::set('messages.greeting', 'Buenas', 'es', ['by' => 'bob']);

    $revisions = Revision::query()->orderBy('id')->get();

    expect($revisions)->toHaveCount(2);
    expect($revisions[0]->old_value)->toBeNull();
    expect($revisions[0]->new_value)->toBe('Hola');
    expect($revisions[1]->old_value)->toBe('Hola');
    expect($revisions[1]->new_value)->toBe('Buenas');
    expect($revisions[1]->changed_by)->toBe('bob');
});

it('rolls a message back to a previous revision', function (): void {
    Translations::set('messages.greeting', 'Hola', 'es');
    $first = Revision::query()->latest('id')->first();

    Translations::set('messages.greeting', 'Buenas', 'es');

    Translations::revisions()->toRevision($first);

    expect(Translations::get('messages.greeting', 'es'))->toBe('Hola');
});

it('bulk rolls back every change made by a contributor', function (): void {
    Translations::set('messages.a', 'Base A', 'en');
    Translations::set('messages.b', 'Base B', 'en');

    Translations::set('messages.a', 'Edit A', 'es', ['by' => 'mallory']);
    Translations::set('messages.b', 'Edit B', 'es', ['by' => 'mallory']);

    $result = Translations::revisions()->byMember('mallory');

    expect($result['rolled_back'])->toBe(2);
    expect(Translations::get('messages.a', 'es'))->toBeNull();
});

it('does not record a revision when revisions are disabled', function (): void {
    config()->set('translations.revisions.enabled', false);

    Translations::set('messages.greeting', 'Hola', 'es');

    expect(Revision::query()->count())->toBe(0);
});

it('treats resaving the same value as a no-op instead of a new record', function (): void {
    $message = Translations::set('messages.greeting', 'Hola', 'es');
    Translations::review()->approve($message);

    expect($message->fresh()->status)->toBe(MessageStatus::Approved);

    $resaved = Translations::set('messages.greeting', 'Hola', 'es', ['by' => 'carol']);

    expect($resaved->status)->toBe(MessageStatus::Approved);
    expect(Revision::query()->count())->toBe(1);
    expect(Revision::query()->latest('id')->first()->changed_by)->not->toBe('carol');
});
