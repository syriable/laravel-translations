<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Syriable\Translations\Models\Member;

it('resolves the current member by matching the authenticated user email', function (): void {
    $member = Member::query()->create([
        'name' => 'Maria',
        'email' => 'maria@example.com',
        'role' => 'translator',
    ]);

    Auth::login(new GenericUser(['id' => 1, 'email' => 'maria@example.com']));

    expect(Member::current()?->id)->toBe($member->id);
});

it('returns null for current member when nobody is authenticated', function (): void {
    expect(Member::current())->toBeNull();
});

it('returns null for current member when no member matches the authenticated email', function (): void {
    Auth::login(new GenericUser(['id' => 1, 'email' => 'nobody@example.com']));

    expect(Member::current())->toBeNull();
});
