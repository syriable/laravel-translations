<?php

use Illuminate\Support\Arr;

function flattenMessages(string $locale): array
{
    $messages = require __DIR__."/../../lang/{$locale}/messages.php";

    return Arr::dot($messages);
}

it('registers the translations namespace', function (): void {
    app('translator')->setLocale('en');

    expect(__('translations::messages.import.done'))->toBe('Import complete.');
});

it('substitutes placeholders in package translations', function (): void {
    app('translator')->setLocale('en');

    expect(__('translations::messages.translate.done', ['count' => 3, 'code' => 'es']))
        ->toBe('Translated 3 messages into [es].');
});

it('resolves localized strings for each shipped locale', function (string $locale, string $expected): void {
    app('translator')->setLocale($locale);

    expect(__('translations::messages.import.done'))->toBe($expected);
})->with([
    ['en', 'Import complete.'],
    ['ar', 'اكتمل الاستيراد.'],
    ['ur', 'درآمد مکمل ہوگئی۔'],
]);

it('keeps the same keys across every shipped locale', function (string $locale): void {
    $base = array_keys(flattenMessages('en'));
    $target = array_keys(flattenMessages($locale));

    sort($base);
    sort($target);

    expect($target)->toBe($base);
})->with(['ar', 'ur']);

it('keeps placeholders identical across every shipped locale', function (string $locale): void {
    $extractPlaceholders = function (array $messages): array {
        $placeholders = [];

        foreach ($messages as $key => $value) {
            preg_match_all('/:[A-Za-z][A-Za-z0-9_]*/', (string) $value, $matches);
            sort($matches[0]);
            $placeholders[$key] = $matches[0];
        }

        return $placeholders;
    };

    expect($extractPlaceholders(flattenMessages($locale)))
        ->toBe($extractPlaceholders(flattenMessages('en')));
})->with(['ar', 'ur']);
