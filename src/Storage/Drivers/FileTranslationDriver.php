<?php

declare(strict_types=1);

namespace Syriable\Translations\Storage\Drivers;

use Illuminate\Filesystem\Filesystem;
use Syriable\Translations\Contracts\TranslationDriver;
use Syriable\Translations\Domain\Catalog;
use Syriable\Translations\Domain\Enums\KeyType;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\LocaleCatalog;
use Syriable\Translations\Storage\FormatRegistry;
use Syriable\Translations\Support\KeyRouter;

/**
 * Reads and writes the translation catalog directly from Laravel language
 * files: PHP group files, a per-locale JSON file, and vendor namespaces.
 */
final class FileTranslationDriver implements TranslationDriver
{
    /**
     * @param  array{sort_keys?: bool}  $output
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly FormatRegistry $formats,
        private readonly KeyRouter $router,
        private readonly string $path,
        private readonly array $output = [],
    ) {}

    public function locales(): array
    {
        $locales = [];

        if ($this->files->isDirectory($this->path)) {
            foreach ($this->files->directories($this->path) as $directory) {
                $name = basename($directory);

                if ($name !== 'vendor') {
                    $locales[$name] = true;
                }
            }

            foreach ($this->files->glob($this->path.'/*.json') as $file) {
                $locales[basename($file, '.json')] = true;
            }
        }

        $codes = array_keys($locales);
        sort($codes);

        return $codes;
    }

    public function read(Locale $locale): LocaleCatalog
    {
        $catalog = new LocaleCatalog($locale);

        $this->readPhpGroups($catalog, $this->path.'/'.$locale->code, null);
        $this->readJson($catalog, $locale);
        $this->readVendor($catalog, $locale);

        return $catalog;
    }

    public function readAll(): Catalog
    {
        $catalog = new Catalog;

        foreach ($this->locales() as $code) {
            $catalog->add($this->read(new Locale($code)));
        }

        return $catalog;
    }

    public function write(LocaleCatalog $catalog): void
    {
        $php = [];
        $json = [];
        $vendor = [];

        foreach ($catalog->all() as $key => $value) {
            $routed = $this->router->classify((string) $key);

            if ($routed->type === KeyType::Json) {
                $json[$routed->item] = $value;

                continue;
            }

            if ($routed->namespace !== null) {
                $vendor[$routed->namespace][$routed->group][$routed->item] = $value;

                continue;
            }

            $php[$routed->group][$routed->item] = $value;
        }

        $this->writePhpGroups($php, $this->path.'/'.$catalog->locale->code);
        $this->writeJson($json, $catalog->locale);
        $this->writeVendor($vendor, $catalog->locale);
    }

    private function readPhpGroups(LocaleCatalog $catalog, string $directory, ?string $namespace): void
    {
        if (! $this->files->isDirectory($directory)) {
            return;
        }

        $format = $this->formats->for('php');
        $prefix = $namespace !== null ? $namespace.'::' : '';

        foreach ($this->files->glob($directory.'/*.php') as $file) {
            $group = basename($file, '.php');
            $entries = $format->parse($this->files->get($file));

            foreach ($entries as $item => $value) {
                $catalog->put($prefix.$group.'.'.$item, $value);
            }
        }
    }

    private function readJson(LocaleCatalog $catalog, Locale $locale): void
    {
        $file = $this->path.'/'.$locale->code.'.json';

        if (! $this->files->exists($file)) {
            return;
        }

        foreach ($this->formats->for('json')->parse($this->files->get($file)) as $key => $value) {
            $catalog->put((string) $key, $value);
        }
    }

    private function readVendor(LocaleCatalog $catalog, Locale $locale): void
    {
        $vendorPath = $this->path.'/vendor';

        if (! $this->files->isDirectory($vendorPath)) {
            return;
        }

        foreach ($this->files->directories($vendorPath) as $namespacePath) {
            $namespace = basename($namespacePath);
            $this->readPhpGroups($catalog, $namespacePath.'/'.$locale->code, $namespace);
        }
    }

    /**
     * @param  array<string, array<string, string|null>>  $groups
     */
    private function writePhpGroups(array $groups, string $directory): void
    {
        $format = $this->formats->for('php');

        foreach ($groups as $group => $entries) {
            $this->files->ensureDirectoryExists($directory);
            $this->files->put(
                $directory.'/'.$group.'.php',
                $format->dump($this->sorted($entries)),
            );
        }
    }

    /**
     * @param  array<string, string|null>  $entries
     */
    private function writeJson(array $entries, Locale $locale): void
    {
        if ($entries === []) {
            return;
        }

        $this->files->ensureDirectoryExists($this->path);
        $this->files->put(
            $this->path.'/'.$locale->code.'.json',
            $this->formats->for('json')->dump($this->sorted($entries)),
        );
    }

    /**
     * @param  array<string, array<string, array<string, string|null>>>  $vendor
     */
    private function writeVendor(array $vendor, Locale $locale): void
    {
        foreach ($vendor as $namespace => $groups) {
            $this->writePhpGroups($groups, $this->path.'/vendor/'.$namespace.'/'.$locale->code);
        }
    }

    /**
     * @param  array<string, string|null>  $entries
     * @return array<string, string|null>
     */
    private function sorted(array $entries): array
    {
        if (($this->output['sort_keys'] ?? true) === true) {
            ksort($entries);
        }

        return $entries;
    }
}
