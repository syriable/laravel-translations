<?php

declare(strict_types=1);

namespace Syriable\Translations\Validation;

use Syriable\Translations\Contracts\ValidationRule;
use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;
use Syriable\Translations\Domain\Translation;

/**
 * Runs every configured validation rule against each translated value in the
 * catalog, comparing it to the corresponding source value.
 */
final class ValidationPipeline
{
    /**
     * @param  list<ValidationRule>  $rules
     */
    public function __construct(
        private readonly array $rules,
        private readonly string $sourceLocale,
    ) {}

    public function validate(Catalog $catalog, ?string $onlyLocale = null): ValidationReport
    {
        $source = $catalog->source();

        if ($source === null) {
            return new ValidationReport;
        }

        $issues = [];

        foreach ($catalog->all() as $code => $localeCatalog) {
            if ($code === $this->sourceLocale || ($onlyLocale !== null && $code !== $onlyLocale)) {
                continue;
            }

            $issues = [...$issues, ...$this->validateLocale($source, $localeCatalog)];
        }

        return new ValidationReport($issues);
    }

    /**
     * Validate a single translation value against its source value. Returns an
     * empty report for the source locale or when either value is missing.
     */
    public function validateKey(string $locale, string $key, ?string $sourceValue, ?string $targetValue): ValidationReport
    {
        if ($locale === $this->sourceLocale) {
            return new ValidationReport;
        }

        $source = new Translation($key, $sourceValue);
        $target = new Translation($key, $targetValue);

        if ($source->isMissing() || $target->isMissing()) {
            return new ValidationReport;
        }

        return new ValidationReport($this->runRules($source, $target, new Locale($locale)));
    }

    /**
     * @return list<Issue>
     */
    private function validateLocale(LocaleCatalog $source, LocaleCatalog $target): array
    {
        $issues = [];

        foreach ($source->keys() as $key) {
            $sourceTranslation = new Translation($key, $source->get($key));

            if ($sourceTranslation->isMissing() || ! $target->has($key)) {
                continue;
            }

            $targetTranslation = new Translation($key, $target->get($key));

            if ($targetTranslation->isMissing()) {
                continue;
            }

            $issues = [...$issues, ...$this->runRules($sourceTranslation, $targetTranslation, $target->locale)];
        }

        return $issues;
    }

    /**
     * @return list<Issue>
     */
    private function runRules(Translation $source, Translation $target, Locale $locale): array
    {
        $issues = [];

        foreach ($this->rules as $rule) {
            $issues = [...$issues, ...$rule->validate($source, $target, $locale)];
        }

        return $issues;
    }
}
