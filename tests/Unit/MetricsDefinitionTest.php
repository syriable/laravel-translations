<?php

declare(strict_types=1);

use Syriable\Translations\Metrics\BundleCoverageMetric;
use Syriable\Translations\Metrics\TranslationCoverageMetric;
use Syriable\Translations\Metrics\TranslationQualityMetric;
use Syriable\Translations\Metrics\TranslationVelocityMetric;

it('defines translation coverage as an all-time partition metric with coverage formula', function (): void {
    $builder = (new TranslationCoverageMetric)->query();

    expect((new TranslationCoverageMetric)->key())->toBe('translations.coverage')
        ->and($builder->isAllTime())->toBeTrue()
        ->and($builder->formulaExpressions())->toBe([
            'coverage' => 'translated / total * 100',
        ])
        ->and(array_keys($builder->datasetBuilders()))->toBe([
            'total',
            'translated',
            'untranslated',
            'approved',
        ]);
});

it('defines translation quality as an all-time partition metric with weighted quality formula', function (): void {
    config()->set('translations.analytics.quality.weights', [
        'review' => 0.7,
        'validation' => 0.3,
    ]);

    $builder = (new TranslationQualityMetric)->query();
    $formulas = $builder->formulaExpressions();

    expect((new TranslationQualityMetric)->key())->toBe('translations.quality')
        ->and($builder->isAllTime())->toBeTrue()
        ->and($formulas)->toHaveKeys(['review', 'validation', 'quality'])
        ->and($formulas['quality'])->toContain('(review * ')
        ->and($formulas['quality'])->toContain(') + (validation * ')
        ->and(array_keys($builder->datasetBuilders()))->toBe([
            'translated',
            'approved',
            'issues',
        ]);
});

it('defines translation velocity as a revisions metric with a date column', function (): void {
    $builder = (new TranslationVelocityMetric)->query();

    expect((new TranslationVelocityMetric)->key())->toBe('translations.velocity')
        ->and($builder->dateColumnName())->not()->toBeNull()
        ->and(array_keys($builder->datasetBuilders()))->toBe(['changes']);
});

it('defines bundle coverage as an all-time partition metric with percent formula', function (): void {
    $builder = (new BundleCoverageMetric)->query();

    expect((new BundleCoverageMetric)->key())->toBe('translations.bundle_coverage')
        ->and($builder->isAllTime())->toBeTrue()
        ->and($builder->formulaExpressions())->toBe([
            'percent' => 'completed_phrases / total_phrases * 100',
        ])
        ->and(array_keys($builder->datasetBuilders()))->toBe([
            'total_phrases',
            'completed_phrases',
        ]);
});
