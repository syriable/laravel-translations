<?php

declare(strict_types=1);

namespace Syriable\Translations\Extraction\Scanners;

use Syriable\Translations\Contracts\Scanner;
use Syriable\Translations\Extraction\AstKeyExtractor;

/**
 * Extracts translation keys from plain PHP source using the AST extractor.
 */
final readonly class PhpScanner implements Scanner
{
    public function __construct(private AstKeyExtractor $extractor) {}

    public function extensions(): array
    {
        return ['php'];
    }

    public function scan(string $contents, string $relativePath): array
    {
        return $this->extractor->extract($contents, $relativePath);
    }
}
