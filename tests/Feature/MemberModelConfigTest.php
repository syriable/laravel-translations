<?php

it('defaults member_model to the app User model class', function (): void {
    expect(config('translations.member_model'))->toBe(App\Models\User::class);
});

it('honours the TRANSLATIONS_MEMBER_MODEL env override', function (): void {
    putenv('TRANSLATIONS_MEMBER_MODEL=App\\Models\\CustomMember');
    $_ENV['TRANSLATIONS_MEMBER_MODEL'] = 'App\\Models\\CustomMember';

    $config = require __DIR__.'/../../config/translations.php';

    putenv('TRANSLATIONS_MEMBER_MODEL');
    unset($_ENV['TRANSLATIONS_MEMBER_MODEL']);

    expect($config['member_model'])->toBe('App\\Models\\CustomMember');
});
