<?php

declare(strict_types=1);

namespace Syriable\Translations\Storage\Formats;

use Syriable\Translations\Contracts\FileFormat;

final class JsonFormat implements FileFormat
{
    public function __construct(
        private readonly int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
    ) {}

    public function extension(): string
    {
        return 'json';
    }

    public function parse(string $contents): array
    {
        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return [];
        }

        $entries = [];

        foreach ($decoded as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $entries[(string) $key] = $value === null ? null : (string) $value;
        }

        return $entries;
    }

    public function dump(array $entries): string
    {
        return json_encode($entries, $this->flags | JSON_THROW_ON_ERROR)."\n";
    }
}
