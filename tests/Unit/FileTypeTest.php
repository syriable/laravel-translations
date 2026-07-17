<?php

use Syriable\Translations\Scanning\FileType;

it('classifies blade templates as blade, never plain php', function (): void {
    expect(FileType::forPath('resources/views/welcome.blade.php'))->toBe('blade');
});

it('classifies vue and react files by extension', function (): void {
    expect(FileType::forPath('resources/js/Pages/Welcome.vue'))->toBe('vue')
        ->and(FileType::forPath('resources/js/pages/welcome.jsx'))->toBe('react')
        ->and(FileType::forPath('resources/js/pages/welcome.tsx'))->toBe('react');
});

it('falls back to the bare extension, defaulting to php', function (): void {
    expect(FileType::forPath('app/Http/Controllers/HomeController.php'))->toBe('php')
        ->and(FileType::forPath('resources/views/emails/invoice.twig'))->toBe('twig')
        ->and(FileType::forPath('artisan'))->toBe('php');
});

it('classifies SplFileInfo instances by their full path', function (): void {
    expect(FileType::forFile(new SplFileInfo('/var/www/resources/views/home.blade.php')))->toBe('blade');
});
