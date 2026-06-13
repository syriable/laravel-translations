<?php

use Syriable\Translations\Files\LangReader;
use Syriable\Translations\Files\LangWriter;

it('writes nested php arrays from dotted keys and reads them back', function (): void {
    $path = sys_get_temp_dir().'/syriable-writer/'.uniqid().'/auth.php';

    (new LangWriter)->writePhp($path, [
        'failed' => 'These credentials do not match.',
        'throttle.message' => 'Too many attempts.',
    ]);

    expect((new LangReader)->readPhp($path))->toBe([
        'failed' => 'These credentials do not match.',
        'throttle.message' => 'Too many attempts.',
    ]);
});

it('sorts keys when requested', function (): void {
    $path = sys_get_temp_dir().'/syriable-writer/'.uniqid().'/sorted.php';

    (new LangWriter)->writePhp($path, ['zebra' => 'z', 'apple' => 'a'], sortKeys: true);

    $contents = file_get_contents($path);

    expect(strpos($contents, 'apple'))->toBeLessThan(strpos($contents, 'zebra'));
});

it('writes pretty json with unescaped unicode', function (): void {
    $path = sys_get_temp_dir().'/syriable-writer/'.uniqid().'/ar.json';

    (new LangWriter)->writeJson($path, ['Welcome' => 'مرحبا']);

    expect(file_get_contents($path))->toContain('مرحبا');
});
