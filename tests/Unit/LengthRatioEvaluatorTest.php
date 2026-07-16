<?php

declare(strict_types=1);

use Syriable\Translations\Quality\LengthRatio\LengthRatioEvaluator;

beforeEach(function (): void {
    config()->set('translations.quality.length_ratio', [
        'min' => 0.5,
        'max' => 2.0,
        'default_density' => 1.0,
        'profiles' => [
            'cjk' => [
                'locales' => ['zh', 'ja', 'ko'],
                'density' => 2.8,
            ],
        ],
        'densities' => [],
        'overrides' => [],
    ]);
});

it('applies the cjk density profile to chinese japanese and korean locale variants', function (string $locale): void {
    $evaluator = new LengthRatioEvaluator;

    expect($evaluator->density($locale))->toBe(2.8)
        ->and($evaluator->profileFor($locale))->not->toBeNull();
})->with(['zh', 'zh-Hans', 'zh_CN', 'ja', 'ja-JP', 'ko', 'ko-KR']);

it('keeps latin locales at the default density', function (string $locale): void {
    expect((new LengthRatioEvaluator)->density($locale))->toBe(1.0);
})->with(['en', 'es', 'fr', 'de', 'pt-BR']);

it('accepts a short but valid chinese translation against english source text', function (): void {
    $source = 'These credentials do not match our records.';
    $target = '这些凭据与我们的记录不符。';

    $evaluation = (new LengthRatioEvaluator)->evaluate($source, 'en', $target, 'zh');

    expect($evaluation['target_density'])->toBe(2.8)
        ->and($evaluation['ratio'])->toBeGreaterThanOrEqual($evaluation['min'])
        ->and($evaluation['ratio'])->toBeLessThanOrEqual($evaluation['max']);
});

it('accepts typical japanese and korean lengths against english source text', function (string $locale, string $target): void {
    $source = 'Too many login attempts. Please try again in :seconds seconds.';

    $evaluation = (new LengthRatioEvaluator)->evaluate($source, 'en', $target, $locale);

    expect($evaluation['ratio'])->toBeGreaterThanOrEqual($evaluation['min'])
        ->and($evaluation['ratio'])->toBeLessThanOrEqual($evaluation['max']);
})->with([
    'japanese' => ['ja', 'ログイン試行回数が多すぎます。:seconds 秒後に再試行してください。'],
    'korean' => ['ko', '로그인 시도가 너무 많습니다. :seconds초 후에 다시 시도하세요.'],
]);

it('still flags a genuinely truncated cjk translation', function (): void {
    $source = 'These credentials do not match our records.';
    $target = '错';

    $evaluation = (new LengthRatioEvaluator)->evaluate($source, 'en', $target, 'zh');

    expect($evaluation['ratio'])->toBeLessThan($evaluation['min']);
});

it('leaves latin-to-latin ratios unchanged from raw character comparison', function (): void {
    $source = 'These credentials do not match our records.';
    $target = 'Estas credenciales no coinciden con nuestros registros.';

    $evaluation = (new LengthRatioEvaluator)->evaluate($source, 'en', $target, 'es');
    $raw = mb_strlen($target) / mb_strlen($source);

    expect($evaluation['source_density'])->toBe(1.0)
        ->and($evaluation['target_density'])->toBe(1.0)
        ->and($evaluation['ratio'])->toEqualWithDelta($raw, 0.0001);
});

it('lets custom locale overrides take precedence over profile and global bounds', function (): void {
    config()->set('translations.quality.length_ratio.overrides', [
        'zh' => ['min' => 0.95, 'max' => 1.05],
    ]);

    $source = 'These credentials do not match our records.';
    $target = '这些凭据与我们的记录不符。';

    $evaluation = (new LengthRatioEvaluator)->evaluate($source, 'en', $target, 'zh-Hans');

    expect($evaluation['min'])->toBe(0.95)
        ->and($evaluation['max'])->toBe(1.05)
        ->and($evaluation['ratio'])->toBeLessThan($evaluation['min']);
});

it('falls back to built-in cjk profiles when config omits profiles', function (): void {
    config()->set('translations.quality.length_ratio', [
        'min' => 0.5,
        'max' => 2.0,
        'overrides' => [],
    ]);

    expect((new LengthRatioEvaluator)->density('zh'))->toBe(2.8);
});

it('lets per-locale density config override the profile density', function (): void {
    config()->set('translations.quality.length_ratio.densities', [
        'zh' => 4.0,
    ]);

    expect((new LengthRatioEvaluator)->density('zh-Hans'))->toBe(4.0);
});
