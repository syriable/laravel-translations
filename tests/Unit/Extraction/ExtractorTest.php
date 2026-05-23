<?php

declare(strict_types=1);

use Syriable\Translations\Extraction\AstKeyExtractor;
use Syriable\Translations\Extraction\Extractor;
use Syriable\Translations\Extraction\Scanners\BladeScanner;
use Syriable\Translations\Extraction\Scanners\PhpScanner;
use Syriable\Translations\Support\FileFinder;

function makeExtractor(string $base): Extractor
{
    $ast = new AstKeyExtractor(['__', 'trans', 'trans_choice']);

    return new Extractor(
        new FileFinder,
        [new PhpScanner($ast), new BladeScanner($ast)],
        ['vendor', 'node_modules'],
        $base,
    );
}

it('extracts static keys from php source', function () {
    $base = fixturePath('source');

    $result = makeExtractor($base)->extract([$base]);

    expect($result->keyStrings())
        ->toContain('messages.welcome')
        ->toContain('messages.greeting')
        ->toContain('messages.apples');
});

it('extracts keys from blade templates including directives and @php', function () {
    $base = fixturePath('source');

    $result = makeExtractor($base)->extract([$base]);

    expect($result->keyStrings())
        ->toContain('messages.tagline')
        ->toContain('messages.page_title');
});

it('ignores dynamic and concatenated keys', function () {
    $base = fixturePath('source');

    $keys = makeExtractor($base)->extract([$base])->keyStrings();

    expect($keys)->not->toContain('ignored.comment');
    foreach ($keys as $key) {
        expect($key)->not->toContain('$');
    }
});

it('marks trans_choice keys as choice and records references', function () {
    $base = fixturePath('source');

    $result = makeExtractor($base)->extract([$base]);

    expect($result->get('messages.apples')->isChoice)->toBeTrue()
        ->and($result->referencesFor('messages.welcome'))->not->toBeEmpty();
});
