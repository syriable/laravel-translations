<?php

use App\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;

it('defaults member_model to the app User model class', function (): void {
    // Isolate this assertion from whatever `auth.providers.users.model` happens to
    // resolve to elsewhere in the suite (e.g. once real Auth guards are exercised,
    // Laravel/Testbench populate it with their own placeholder). Repository::set(key,
    // null) - what offsetUnset delegates to - leaves the key present with a null
    // value, and Arr::get() returns that null as-is rather than falling through to
    // a default, so the key needs to be genuinely absent to test the fallback. Rebuild
    // the repository from a copy of the real config with just that key removed, rather
    // than swapping in a bare one, so database/etc config survives for test teardown.
    $items = config()->all();
    Arr::forget($items, 'auth.providers.users.model');
    app()->instance('config', new Repository($items));

    $config = require __DIR__.'/../../config/translations.php';

    expect($config['member_model'])->toBe(User::class);
});

it('honours the TRANSLATIONS_MEMBER_MODEL env override', function (): void {
    putenv('TRANSLATIONS_MEMBER_MODEL=App\\Models\\CustomMember');
    $_ENV['TRANSLATIONS_MEMBER_MODEL'] = 'App\\Models\\CustomMember';

    $config = require __DIR__.'/../../config/translations.php';

    putenv('TRANSLATIONS_MEMBER_MODEL');
    unset($_ENV['TRANSLATIONS_MEMBER_MODEL']);

    expect($config['member_model'])->toBe('App\\Models\\CustomMember');
});
