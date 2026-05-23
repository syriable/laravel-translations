<?php

declare(strict_types=1);

use Syriable\Translations\Storage\Formats\JsonFormat;
use Syriable\Translations\Storage\Formats\PhpArrayFormat;

it('parses a php array file into flat dotted keys', function () {
    $entries = (new PhpArrayFormat)->parse(<<<'PHP'
        <?php

        return [
            'welcome' => 'Hi',
            'nested' => ['title' => 'Dashboard'],
        ];
        PHP);

    expect($entries)->toBe([
        'welcome' => 'Hi',
        'nested.title' => 'Dashboard',
    ]);
});

it('returns an empty array for a php file without a return', function () {
    expect((new PhpArrayFormat)->parse('<?php $x = 1;'))->toBe([]);
});

it('round-trips a php array through dump and parse', function () {
    $format = new PhpArrayFormat;
    $entries = ['a.b' => 'one', 'a.c' => 'two', 'd' => 'three'];

    expect($format->parse($format->dump($entries)))->toBe($entries);
});

it('does not execute code when parsing a php file', function () {
    // The throw would abort if the file were executed; reading the return
    // literal without raising proves parsing never runs the code.
    $entries = (new PhpArrayFormat)->parse("<?php throw new RuntimeException('boom'); return ['a' => 'b'];");

    expect($entries)->toBe(['a' => 'b']);
});

it('parses and dumps json keeping keys verbatim', function () {
    $format = new JsonFormat;
    $entries = $format->parse('{"Save changes": "Save changes"}');

    expect($entries)->toBe(['Save changes' => 'Save changes'])
        ->and($format->dump($entries))->toContain('Save changes');
});
