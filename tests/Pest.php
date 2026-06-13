<?php

use Syriable\Translations\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function makeLangFiles(array $tree): string
{
    $base = sys_get_temp_dir().'/syriable-translations-tests/lang';

    if (is_dir($base)) {
        deleteDirectory($base);
    }

    mkdir($base, 0755, true);

    foreach ($tree as $relative => $contents) {
        $path = $base.'/'.$relative;
        @mkdir(dirname($path), 0755, true);

        if (str_ends_with($relative, '.php')) {
            file_put_contents($path, "<?php\n\nreturn ".var_export($contents, true).";\n");

            continue;
        }

        file_put_contents($path, json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    return $base;
}

function deleteDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    foreach (scandir($directory) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $directory.'/'.$entry;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }

    rmdir($directory);
}
