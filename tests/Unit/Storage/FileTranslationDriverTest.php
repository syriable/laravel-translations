<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Storage\Drivers\FileTranslationDriver;
use Syriable\Translations\Storage\Formats\JsonFormat;
use Syriable\Translations\Storage\Formats\PhpArrayFormat;
use Syriable\Translations\Storage\FormatRegistry;
use Syriable\Translations\Support\KeyRouter;

function makeDriver(string $path): FileTranslationDriver
{
    return new FileTranslationDriver(
        new Filesystem,
        new FormatRegistry([new PhpArrayFormat, new JsonFormat]),
        new KeyRouter,
        $path,
        ['sort_keys' => true],
    );
}

function copyFixtureLang(): string
{
    $target = sys_get_temp_dir().'/syriable-translations-'.uniqid();
    (new Filesystem)->copyDirectory(fixturePath('lang'), $target);

    return $target;
}

it('discovers php and json locales', function () {
    $locales = makeDriver(fixturePath('lang'))->locales();

    expect($locales)->toContain('en')->toContain('fr');
});

it('reads php groups, nested keys and json strings into a flat catalog', function () {
    $catalog = makeDriver(fixturePath('lang'))->read(new Locale('en'));

    expect($catalog->get('messages.welcome'))->toBe('Welcome')
        ->and($catalog->get('messages.nested.title'))->toBe('Dashboard')
        ->and($catalog->get('Save changes'))->toBe('Save changes');
});

it('round-trips a catalog through disk without losing entries', function () {
    $source = copyFixtureLang();
    $driver = makeDriver($source);

    $catalog = $driver->read(new Locale('en'));
    $driver->write($catalog);

    $reread = $driver->read(new Locale('en'));

    expect($reread->all())->toEqual($catalog->all());

    (new Filesystem)->deleteDirectory($source);
});

it('writes new keys to the correct files based on routing', function () {
    $source = copyFixtureLang();
    $driver = makeDriver($source);

    $catalog = $driver->read(new Locale('en'));
    $catalog->put('buttons.submit', 'Submit');
    $catalog->put('A plain sentence', 'A plain sentence');
    $driver->write($catalog);

    expect(file_exists($source.'/en/buttons.php'))->toBeTrue()
        ->and(makeDriver($source)->read(new Locale('en'))->get('A plain sentence'))->toBe('A plain sentence');

    (new Filesystem)->deleteDirectory($source);
});
