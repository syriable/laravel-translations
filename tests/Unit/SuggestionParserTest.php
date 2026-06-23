<?php

use Syriable\Translations\Ai\SuggestionParser;

it('parses well-formed suggestions and forces one recommended', function (): void {
    $variants = (new SuggestionParser)->parse([
        ['value' => 'uno', 'confidence' => 0.6, 'recommended' => false, 'note' => 'a'],
        ['value' => 'dos', 'confidence' => 0.9, 'recommended' => false, 'note' => 'b'],
    ]);

    expect($variants)->toHaveCount(2);
    expect($variants[1]['recommended'])->toBeTrue();
    expect($variants[0]['recommended'])->toBeFalse();
    expect($variants[1]['confidence'])->toBe(0.9);
});

it('unwraps a suggestion list that the model encoded as JSON inside a single value', function (): void {
    // The model ignored the schema and stuffed the whole array into value[0].
    $blob = json_encode([
        ['value' => '这些凭据与我们的记录不符。', 'confidence' => 0.95, 'recommended' => true, 'note' => '标准译法'],
        ['value' => '这些凭证与我们的记录不匹配。', 'confidence' => 0.90, 'recommended' => false, 'note' => '同样常见'],
        ['value' => '这些身份凭据与我们的记录不一致。', 'confidence' => 0.85, 'recommended' => false, 'note' => '更明确'],
    ], JSON_UNESCAPED_UNICODE);

    $variants = (new SuggestionParser)->parse([
        ['value' => $blob, 'confidence' => null, 'recommended' => true, 'note' => null],
    ]);

    expect($variants)->toHaveCount(3);
    expect($variants[0]['value'])->toBe('这些凭据与我们的记录不符。');
    expect($variants[0]['confidence'])->toBe(0.95);
    expect($variants[0]['note'])->toBe('标准译法');
    expect($variants[0]['recommended'])->toBeTrue();
    expect($variants[1]['recommended'])->toBeFalse();
});

it('repairs and unwraps a JSON blob with unescaped quotes inside notes', function (): void {
    // Real-world Anthropic output: the whole list is dumped as a string into
    // value[0], and the notes contain unescaped double quotes, so json_decode
    // alone fails.
    $blob = <<<'JSON'
    [
      {
        "value": "这些凭据与我们的记录不符。",
        "confidence": 0.95,
        "recommended": true,
        "note": "使用"凭据"作为credentials的标准译法,搭配"与...不符"表达不匹配的含义。"
      },
      {
        "value": "这些凭证与我们的记录不匹配。",
        "confidence": 0.88,
        "recommended": false,
        "note": "采用"凭证"这一同样常见的术语翻译,使用"不匹配"直接对应match。"
      }
    ]
    JSON;

    $variants = (new SuggestionParser)->parse([
        ['value' => $blob, 'confidence' => null, 'recommended' => true, 'note' => null],
    ]);

    expect($variants)->toHaveCount(2);
    expect($variants[0]['value'])->toBe('这些凭据与我们的记录不符。');
    expect($variants[0]['confidence'])->toBe(0.95);
    expect($variants[0]['recommended'])->toBeTrue();
    expect($variants[0]['note'])->toContain('凭据');
    expect($variants[1]['value'])->toBe('这些凭证与我们的记录不匹配。');
    expect($variants[1]['recommended'])->toBeFalse();
});

it('unwraps a fenced and suggestions-wrapped JSON payload', function (): void {
    $blob = "```json\n".json_encode([
        'suggestions' => [
            ['value' => 'Bonjour', 'confidence' => 0.8, 'note' => 'salutation courante'],
        ],
    ], JSON_UNESCAPED_UNICODE)."\n```";

    $variants = (new SuggestionParser)->parse([
        ['value' => $blob, 'recommended' => false],
    ]);

    expect($variants)->toHaveCount(1);
    expect($variants[0]['value'])->toBe('Bonjour');
    expect($variants[0]['note'])->toBe('salutation courante');
    expect($variants[0]['recommended'])->toBeTrue();
});

it('leaves a plain translation untouched', function (): void {
    $variants = (new SuggestionParser)->parse([
        ['value' => 'Hello [world]', 'confidence' => 0.7, 'recommended' => true, 'note' => 'natural'],
    ]);

    expect($variants)->toHaveCount(1);
    expect($variants[0]['value'])->toBe('Hello [world]');
});

it('accepts suggestions returned as bare strings instead of objects', function (): void {
    // The model returned the suggestions as a plain array of strings.
    $variants = (new SuggestionParser)->parse([
        '这些凭据与我们的记录不符。',
        '这些凭证与我们的记录不匹配。',
    ]);

    expect($variants)->toHaveCount(2);
    expect($variants[0]['value'])->toBe('这些凭据与我们的记录不符。');
    expect($variants[0]['confidence'])->toBeNull();
    expect($variants[0]['note'])->toBeNull();
    expect($variants[0]['recommended'])->toBeTrue();
});

it('unwraps a bare string that is itself an encoded suggestion list', function (): void {
    $blob = json_encode([
        ['value' => '你好', 'confidence' => 0.9, 'note' => '常用问候语'],
        ['value' => '您好', 'confidence' => 0.8, 'note' => '更正式'],
    ], JSON_UNESCAPED_UNICODE);

    $variants = (new SuggestionParser)->parse([$blob]);

    expect($variants)->toHaveCount(2);
    expect($variants[0]['value'])->toBe('你好');
    expect($variants[0]['note'])->toBe('常用问候语');
});

it('handles a single suggestion object passed directly', function (): void {
    $variants = (new SuggestionParser)->parse([
        'value' => 'Hola', 'confidence' => 0.9, 'note' => 'saludo',
    ]);

    expect($variants)->toHaveCount(1);
    expect($variants[0]['value'])->toBe('Hola');
    expect($variants[0]['recommended'])->toBeTrue();
});

it('returns nothing for unusable payloads', function (): void {
    expect((new SuggestionParser)->parse(null))->toBe([]);
    expect((new SuggestionParser)->parse([]))->toBe([]);
    expect((new SuggestionParser)->parse(42))->toBe([]);
});

it('drops empty values and normalizes missing fields', function (): void {
    $variants = (new SuggestionParser)->parse([
        ['value' => '  '],
        ['value' => 'kept'],
    ]);

    expect($variants)->toHaveCount(1);
    expect($variants[0])->toMatchArray([
        'value' => 'kept',
        'confidence' => null,
        'recommended' => true,
        'note' => null,
    ]);
});
