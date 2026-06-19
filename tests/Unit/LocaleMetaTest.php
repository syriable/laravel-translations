<?php

use Syriable\Translations\Support\LocaleMeta;

it('accepts known and well-formed locale codes', function (string $code): void {
    expect(LocaleMeta::isValidCode($code))->toBeTrue();
})->with(['en', 'es', 'fil', 'haw', 'pt-BR', 'zh-Hans', 'en_US', 'xx-custom']);

it('rejects malformed locale codes', function (string $code): void {
    expect(LocaleMeta::isValidCode($code))->toBeFalse();
})->with(['sdfsdgv', 'a', '123', 'en-', 'english', '!!']);
