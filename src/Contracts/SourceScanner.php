<?php

namespace Syriable\Translations\Contracts;

interface SourceScanner
{
    public function supports(string $path): bool;

    public function scan(string $path, string $contents): array;
}
