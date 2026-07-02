<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Syriable\Translations\Enums\MemberRole;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Activity;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Policies\MessagePolicy;
use Syriable\Translations\Tests\Fixtures\Member;

beforeEach(function (): void {
    Schema::create('fixture_members', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('role')->default('translator');
        $table->timestamps();
    });

    config()->set('translations.member_model', Member::class);
});

afterEach(function (): void {
    Schema::dropIfExists('fixture_members');
});

it('assigns locales to a configured member through the pivot table', function (): void {
    $es = Translations::addLocale('es');
    $member = Member::create(['name' => 'Maria', 'role' => 'translator']);

    $es->members()->attach($member->id);

    expect(Locale::find($es->id)->members()->count())->toBe(1);
    expect(Locale::find($es->id)->members()->first()->id)->toBe($member->id);
});

it('resolves activity and comment member relations against the configured model', function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();

    $member = Member::create(['name' => 'Reviewer', 'role' => 'reviewer']);

    $message = Translations::set('messages.greeting', 'Hola', 'es');
    Translations::review()->approve($message, (string) $member->id);

    $activity = Activity::query()->where('action', 'status_changed')->latest('id')->first();
    expect($activity->member)->not->toBeNull();
    expect($activity->member->id)->toBe($member->id);

    $comment = $message->comment('Looks good', (string) $member->id);
    expect($comment->member)->not->toBeNull();
    expect($comment->member->id)->toBe($member->id);
});

it('resolves a MemberRole for a model implementing HasTranslationRole', function (): void {
    $translator = Member::create(['name' => 'Translator', 'role' => 'translator']);
    $reviewer = Member::create(['name' => 'Reviewer', 'role' => 'reviewer']);

    expect($translator->translationRole())->toBe(MemberRole::Translator);
    expect($reviewer->translationRole())->toBe(MemberRole::Reviewer);
});

it('gates message actions through the MessagePolicy stub', function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();

    $message = Translations::set('messages.greeting', 'Hola', 'es');

    $translator = Member::create(['name' => 'Translator', 'role' => 'translator']);
    $reviewer = Member::create(['name' => 'Reviewer', 'role' => 'reviewer']);
    $viewer = Member::create(['name' => 'Viewer', 'role' => 'viewer']);

    $policy = new MessagePolicy;

    expect($policy->translate($translator, $message))->toBeTrue();
    expect($policy->review($translator, $message))->toBeFalse();
    expect($policy->review($reviewer, $message))->toBeTrue();
    expect($policy->manage($reviewer, $message))->toBeFalse();
    expect($policy->translate($viewer, $message))->toBeFalse();
});
