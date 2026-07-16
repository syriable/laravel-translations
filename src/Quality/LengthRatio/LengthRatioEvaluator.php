<?php

declare(strict_types=1);

namespace Syriable\Translations\Quality\LengthRatio;

/**
 * Resolves locale-aware character densities and length-ratio bounds so
 * high-density scripts (CJK, etc.) can be compared fairly against Latin source text.
 *
 * Resolution order for density: per-locale config → matching profile → built-in defaults → 1.0.
 * Resolution order for bounds: per-locale override → profile bounds → global min/max.
 */
final class LengthRatioEvaluator
{
    /**
     * Built-in density profiles used when config does not define `profiles`
     * (e.g. an older published config). Config profiles replace these entirely
     * when present and non-empty.
     *
     * @var array<string, array{locales: list<string>, density: float}>
     */
    private const array DEFAULT_PROFILES = [
        'cjk' => [
            'locales' => ['zh', 'ja', 'ko'],
            'density' => 2.8,
        ],
    ];

    /**
     * @return array{ratio: float, min: float, max: float, source_density: float, target_density: float}
     */
    public function evaluate(string $sourceValue, string $sourceLocale, string $targetValue, string $targetLocale): array
    {
        $sourceDensity = $this->density($sourceLocale);
        $targetDensity = $this->density($targetLocale);
        $sourceUnits = mb_strlen($sourceValue) * $sourceDensity;
        $targetUnits = mb_strlen($targetValue) * $targetDensity;
        [$min, $max] = $this->bounds($targetLocale);

        return [
            'ratio' => $sourceUnits > 0 ? $targetUnits / $sourceUnits : 0.0,
            'min' => $min,
            'max' => $max,
            'source_density' => $sourceDensity,
            'target_density' => $targetDensity,
        ];
    }

    public function density(string $localeCode): float
    {
        $config = $this->config();
        $base = $this->baseCode($localeCode);
        $densities = $config['densities'] ?? [];

        if (isset($densities[$localeCode]) && is_numeric($densities[$localeCode])) {
            return (float) $densities[$localeCode];
        }

        if (isset($densities[$base]) && is_numeric($densities[$base])) {
            return (float) $densities[$base];
        }

        $profile = $this->profileFor($localeCode);

        if ($profile !== null && isset($profile['density']) && is_numeric($profile['density'])) {
            return (float) $profile['density'];
        }

        return (float) ($config['default_density'] ?? 1.0);
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function bounds(string $localeCode): array
    {
        $config = $this->config();
        $override = $this->overrideFor($localeCode);
        $profile = $this->profileFor($localeCode);

        return [
            (float) ($override['min'] ?? $profile['min'] ?? $config['min'] ?? 0.5),
            (float) ($override['max'] ?? $profile['max'] ?? $config['max'] ?? 2.0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profileFor(string $localeCode): ?array
    {
        $base = $this->baseCode($localeCode);

        foreach ($this->profiles() as $profile) {
            $locales = $profile['locales'] ?? [];

            if (! is_array($locales)) {
                continue;
            }

            $normalized = array_map(
                fn (mixed $code): string => $this->baseCode((string) $code),
                $locales,
            );

            if (in_array($base, $normalized, true)) {
                return $profile;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>|array<string, array<string, mixed>>
     */
    private function profiles(): array
    {
        $configured = $this->config()['profiles'] ?? null;

        if (is_array($configured) && $configured !== []) {
            /** @var list<mixed>|array<string, mixed> $configured */
            return array_values(array_filter($configured, 'is_array'));
        }

        return array_values(self::DEFAULT_PROFILES);
    }

    /**
     * @return array<string, mixed>
     */
    private function overrideFor(string $localeCode): array
    {
        $overrides = $this->config()['overrides'] ?? [];

        if (! is_array($overrides)) {
            return [];
        }

        if (isset($overrides[$localeCode]) && is_array($overrides[$localeCode])) {
            return $overrides[$localeCode];
        }

        $base = $this->baseCode($localeCode);

        if (isset($overrides[$base]) && is_array($overrides[$base])) {
            return $overrides[$base];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $config = config('translations.quality.length_ratio', []);

        return is_array($config) ? $config : [];
    }

    private function baseCode(string $localeCode): string
    {
        return strtolower(explode('_', str_replace('-', '_', $localeCode))[0]);
    }
}
