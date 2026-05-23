<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Syriable\Translations\Domain\Enums\RevisionType;
use Syriable\Translations\Models\TranslationRevision;

function makeRevision(string $key, Carbon $createdAt): void
{
    TranslationRevision::query()->create([
        'locale' => 'en',
        'translation_key' => $key,
        'key_hash' => TranslationRevision::hashKey($key),
        'old_value' => null,
        'new_value' => 'x',
        'change_type' => RevisionType::Created,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

it('deletes revisions older than the retention window and keeps recent ones', function () {
    makeRevision('old', Carbon::now()->subDays(120));
    makeRevision('recent', Carbon::now()->subDays(10));

    $this->artisan('translations:revisions:cleanup', ['--days' => 90])
        ->expectsOutputToContain('Deleted 1 revision')
        ->assertSuccessful();

    expect(TranslationRevision::query()->pluck('translation_key')->all())->toBe(['recent']);
});

it('uses the configured retention window by default', function () {
    config()->set('translations.metadata.revisions.prune_after_days', 30);
    makeRevision('old', Carbon::now()->subDays(45));

    $this->artisan('translations:revisions:cleanup')->assertSuccessful();

    expect(TranslationRevision::query()->count())->toBe(0);
});

it('keeps everything when retention is zero', function () {
    config()->set('translations.metadata.revisions.prune_after_days', 0);
    makeRevision('old', Carbon::now()->subDays(999));

    $this->artisan('translations:revisions:cleanup')->assertSuccessful();

    expect(TranslationRevision::query()->count())->toBe(1);
});
