<?php

declare(strict_types=1);

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Validation\Issue;

/**
 * A validation rule compares a translated value against its source value and
 * reports any inconsistencies it finds.
 */
interface ValidationRule
{
    /**
     * A stable identifier for the rule, used in reports and configuration.
     */
    public function id(): string;

    /**
     * @return list<Issue>
     */
    public function validate(Translation $source, Translation $target, Locale $locale): array;
}
