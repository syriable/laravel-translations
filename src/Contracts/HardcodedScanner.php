<?php

declare(strict_types=1);

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Detection\DetectedString;

/**
 * Finds hardcoded user-facing strings within a single source file that should
 * arguably be translated.
 */
interface HardcodedScanner
{
    /**
     * File extensions this scanner handles, without the leading dot.
     *
     * @return list<string>
     */
    public function extensions(): array;

    /**
     * @return list<DetectedString>
     */
    public function scan(string $contents, string $relativePath): array;
}
