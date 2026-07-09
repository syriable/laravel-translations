<?php

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Syriable\Translations\Contracts\ResolvesActor;
use Syriable\Translations\Enums\RevisionReason;
use Syriable\Translations\Facades\Translations;
use Syriable\Translations\Models\Locale;
use Syriable\Translations\Models\QualityIssue;
use Syriable\Translations\Models\Revision;
use Syriable\Translations\Quality\Inspector;
use Syriable\Translations\Tests\Fixtures\Member;

beforeEach(function (): void {
    Translations::addLocale('en', ['is_source' => true]);
    Translations::addLocale('es');
    Locale::flushSourceCache();
});

it('leaves changed_by null when nobody is authenticated and none was given', function (): void {
    Translations::set('messages.greeting', 'Hola', 'es');

    expect(Revision::query()->latest('id')->first()->changed_by)->toBeNull();
});

it('falls back to the configured system_actor when nobody is authenticated', function (): void {
    config()->set('translations.system_actor', 'system');

    $message = Translations::set('messages.greeting', 'Hola', 'es');

    expect($message->translated_by)->toBe('system');
    expect(Revision::query()->latest('id')->first()->changed_by)->toBe('system');
});

it('auto-resolves changed_by and translated_by from the bound actor resolver', function (): void {
    $this->app->instance(ResolvesActor::class, new class implements ResolvesActor
    {
        public function resolve(): ?string
        {
            return '42';
        }
    });

    $message = Translations::set('messages.greeting', 'Hola', 'es');

    expect($message->translated_by)->toBe('42');
    expect(Revision::query()->latest('id')->first()->changed_by)->toBe('42');
});

it('lets an explicit by override the resolved actor', function (): void {
    $this->app->instance(ResolvesActor::class, new class implements ResolvesActor
    {
        public function resolve(): ?string
        {
            return '42';
        }
    });

    $message = Translations::set('messages.greeting', 'Hola', 'es', ['by' => 'alice']);

    expect($message->translated_by)->toBe('alice');
    expect(Revision::query()->latest('id')->first()->changed_by)->toBe('alice');
});

it('auto-resolves who activated an AI translation, not the AI itself', function (): void {
    $this->app->instance(ResolvesActor::class, new class implements ResolvesActor
    {
        public function resolve(): ?string
        {
            return '7';
        }
    });

    $this->app->instance(
        Syriable\Translations\Contracts\Translator::class,
        new Syriable\Translations\Ai\FakeTranslator(fn ($request) => 'TR:'.$request->text)
    );

    Translations::set('messages.greeting', 'Hello there', 'en');
    $message = Translations::translate('messages.greeting', 'es');

    expect($message->translated_by)->toBe('7');
    $revision = Revision::query()->latest('id')->first();
    expect($revision->reason)->toBe(RevisionReason::Ai);
    expect($revision->changed_by)->toBe('7');
});

it('auto-resolves reviewed_by on approve and reject when no reviewer is passed', function (): void {
    $this->app->instance(ResolvesActor::class, new class implements ResolvesActor
    {
        public function resolve(): ?string
        {
            return '9';
        }
    });

    $message = Translations::set('messages.greeting', 'Hola', 'es');

    Translations::review()->approve($message);
    expect($message->fresh()->reviewed_by)->toBe('9');

    Translations::review()->reject($message, 'needs work');
    expect($message->fresh()->reviewed_by)->toBe('9');
});

it('resolves the real Laravel auth guard through the default resolver', function (): void {
    config()->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
    config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => Member::class]);
    config()->set('auth.defaults.guard', 'web');

    Auth::guard('web')->setUser(new GenericUser(['id' => 55]));

    $message = Translations::set('messages.greeting', 'Hola', 'es');

    expect($message->translated_by)->toBe('55');
});

it('records a revision for a quality auto-fix and stamps who ran it', function (): void {
    config()->set('translations.quality.run_on_save', false);

    Translations::set('messages.label', 'Save changes', 'en');
    $message = Translations::set('messages.label', '  save changes  ', 'es');

    $inspector = app(Inspector::class);
    $inspector->inspectAndStore($message->fresh(['locale']));

    foreach (QualityIssue::query()->where('fixable', true)->get() as $issue) {
        $inspector->fix($issue, 'qa-bot');
    }

    $revision = Revision::query()->where('reason', RevisionReason::QualityFix)->latest('id')->first();
    expect($revision)->not->toBeNull();
    expect($revision->changed_by)->toBe('qa-bot');
    expect(Translations::get('messages.label', 'es'))->toBe('Save changes');
});

describe('member relations', function (): void {
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

    it('resolves Revision::member() against the configured member model', function (): void {
        $member = Member::create(['name' => 'Alice']);

        $message = Translations::set('messages.greeting', 'Hola', 'es', ['by' => (string) $member->id]);
        $revision = Revision::query()->where('message_id', $message->id)->latest('id')->first();

        expect($revision->member)->not->toBeNull();
        expect($revision->member->id)->toBe($member->id);
    });

    it('resolves Message::translator() and Message::reviewer() against the configured member model', function (): void {
        $translator = Member::create(['name' => 'Translator']);
        $reviewer = Member::create(['name' => 'Reviewer']);

        $message = Translations::set('messages.greeting', 'Hola', 'es', ['by' => (string) $translator->id]);
        Translations::review()->approve($message, (string) $reviewer->id);

        $message = $message->fresh();

        expect($message->translator)->not->toBeNull();
        expect($message->translator->id)->toBe($translator->id);
        expect($message->reviewer)->not->toBeNull();
        expect($message->reviewer->id)->toBe($reviewer->id);
    });
});
