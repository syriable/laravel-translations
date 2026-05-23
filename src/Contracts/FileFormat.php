<?php

declare(strict_types=1);

namespace Syriable\Translations\Contracts;

/**
 * A file format serialises and deserialises a flat map of dotted keys to a
 * concrete on-disk representation (PHP array file, JSON, YAML, ...).
 */
interface FileFormat
{
    /**
     * The file extension this format owns, without the leading dot.
     */
    public function extension(): string;

    /**
     * Parse raw file contents into a flat map of dotted keys to values.
     *
     * @return array<string, string|null>
     */
    public function parse(string $contents): array;

    /**
     * Render a flat map of dotted keys to values back into file contents.
     *
     * @param  array<string, string|null>  $entries
     */
    public function dump(array $entries): string;
}
