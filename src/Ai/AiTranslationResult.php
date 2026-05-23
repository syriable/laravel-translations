<?php

declare(strict_types=1);

namespace Syriable\Translations\Ai;

/**
 * Summary of an AI translation run.
 */
final readonly class AiTranslationResult
{
    public function __construct(
        public int $translated,
        public int $skipped,
    ) {}
}
