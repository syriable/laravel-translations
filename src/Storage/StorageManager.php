<?php

declare(strict_types=1);

namespace Syriable\Translations\Storage;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Syriable\Translations\Contracts\TranslationDriver;
use Syriable\Translations\Exceptions\DriverNotFoundException;
use Syriable\Translations\Storage\Drivers\FileTranslationDriver;
use Syriable\Translations\Support\KeyRouter;

/**
 * Resolves and caches translation storage drivers from configuration, and lets
 * applications register custom drivers via {@see extend()}.
 */
final class StorageManager
{
    /**
     * @var array<string, TranslationDriver>
     */
    private array $resolved = [];

    /**
     * @var array<string, Closure(array<string, mixed>): TranslationDriver>
     */
    private array $customCreators = [];

    /**
     * @param  array<string, mixed>  $config  the package configuration array
     */
    public function __construct(
        private readonly Filesystem $files,
        private readonly FormatRegistry $formats,
        private readonly KeyRouter $router,
        private readonly array $config,
    ) {}

    public function driver(?string $name = null): TranslationDriver
    {
        $name ??= $this->config['storage']['default'] ?? 'file';

        return $this->resolved[$name] ??= $this->resolve($name);
    }

    /**
     * @param  Closure(array<string, mixed>): TranslationDriver  $creator
     */
    public function extend(string $name, Closure $creator): self
    {
        $this->customCreators[$name] = $creator;

        return $this;
    }

    private function resolve(string $name): TranslationDriver
    {
        if (isset($this->customCreators[$name])) {
            return ($this->customCreators[$name])($this->config);
        }

        $config = $this->config['storage']['drivers'][$name] ?? null;

        if ($config === null) {
            throw DriverNotFoundException::named($name);
        }

        return match ($config['driver'] ?? $name) {
            'file' => $this->createFileDriver($config),
            default => throw DriverNotFoundException::named($name),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function createFileDriver(array $config): FileTranslationDriver
    {
        return new FileTranslationDriver(
            $this->files,
            $this->formats,
            $this->router,
            $config['path'] ?? ($this->config['lang_path'] ?? ''),
            $this->config['storage']['output'] ?? [],
        );
    }
}
