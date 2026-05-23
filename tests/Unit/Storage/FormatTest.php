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

it('keeps valid keys when some values are not constant expressions', function () {
    $entries = (new PhpArrayFormat)->parse(<<<'PHP'
        <?php

        return [
            'welcome' => 'Hi',
            'dynamic' => trans('other.key'),
            'runtime' => 'prefix-'.$suffix,
            'constant' => 'foo'.'bar',
            'goodbye' => 'Bye',
        ];
        PHP);

    expect($entries)->toBe([
        'welcome' => 'Hi',
        'constant' => 'foobar',
        'goodbye' => 'Bye',
    ]);
});

it('preserves valid entries inside nested arrays with mixed values', function () {
    $entries = (new PhpArrayFormat)->parse(<<<'PHP'
        <?php

        return [
            'group' => [
                'ok' => 'Yes',
                'bad' => strtoupper('no'),
                'deep' => [
                    'kept' => 'Value',
                    'dropped' => config('app.name'),
                ],
            ],
            'top' => 'Level',
        ];
        PHP);

    expect($entries)->toBe([
        'group.ok' => 'Yes',
        'group.deep.kept' => 'Value',
        'top' => 'Level',
    ]);
});

it('returns an empty array when every value is non-constant', function () {
    $entries = (new PhpArrayFormat)->parse(<<<'PHP'
        <?php

        return [
            'a' => fn () => 'x',
            'b' => $value,
        ];
        PHP);

    expect($entries)->toBe([]);
});

it('evaluates constant scalar expressions in values', function () {
    $entries = (new PhpArrayFormat)->parse(<<<'PHP'
        <?php

        return [
            'count' => 1 + 2,
            'flag' => true,
            'joined' => 'a'.'b'.'c',
        ];
        PHP);

    expect($entries)->toBe([
        'count' => '3',
        'flag' => '1',
        'joined' => 'abc',
    ]);
});

it('parses and dumps json keeping keys verbatim', function () {
    $format = new JsonFormat;
    $entries = $format->parse('{"Save changes": "Save changes"}');

    expect($entries)->toBe(['Save changes' => 'Save changes'])
        ->and($format->dump($entries))->toContain('Save changes');
});
