<?php

declare(strict_types=1);

namespace Syriable\Translations\Contracts;

use Syriable\Translations\Domain\ExtractedKey;

/**
 * A scanner discovers translation key usages within a single source file.
 *
 * Implementations are stateless and resolved from the container, so they may
 * depend on any service. Register custom scanners in config('translations.extraction.scanners').
 */
interface Scanner
{
    /**
     * File extensions this scanner handles, without the leading dot
     * (e.g. ['php'] or ['blade.php']). Longer, more specific extensions win.
     *
     * @return list<string>
     */
    public function extensions(): array;

    /**
     * @return list<ExtractedKey>
     */
    public function scan(string $contents, string $relativePath): array;
}
