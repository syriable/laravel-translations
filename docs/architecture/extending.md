# Extending the package

Every variable behaviour in the package is a contract resolved from
configuration and the service container. To extend it, implement the relevant
contract and register your class. No core code needs to change.

## Add a custom scanner

Support a new source language by implementing `Contracts\Scanner`.

```php
namespace App\Translations;

use Syriable\Translations\Contracts\Scanner;
use Syriable\Translations\Domain\ExtractedKey;
use Syriable\Translations\Domain\SourceReference;
use Syriable\Translations\Domain\TranslationKey;

final class TwigScanner implements Scanner
{
    /** @return list<string> */
    public function extensions(): array
    {
        return ['twig'];
    }

    /** @return list<ExtractedKey> */
    public function scan(string $contents, string $relativePath): array
    {
        // ... discover keys ...
        return [
            new ExtractedKey(
                new TranslationKey('messages.welcome'),
                [new SourceReference($relativePath, 12, 'trans')],
            ),
        ];
    }
}
```

Register it:

```php
// config/translations.php
'extraction' => [
    'scanners' => [
        \Syriable\Translations\Extraction\Scanners\PhpScanner::class,
        \Syriable\Translations\Extraction\Scanners\BladeScanner::class,
        \App\Translations\TwigScanner::class,
    ],
],
```

Scanners are resolved from the container, so you may type-hint dependencies in
their constructor — including the package's own `AstKeyExtractor`.

## Add a custom file format

Support YAML, PO, or any other on-disk format by implementing
`Contracts\FileFormat` and registering it on the `FormatRegistry`.

```php
use Syriable\Translations\Contracts\FileFormat;

final class YamlFormat implements FileFormat
{
    public function extension(): string
    {
        return 'yaml';
    }

    /** @return array<string, string|null> */
    public function parse(string $contents): array
    {
        return Yaml::parse($contents);
    }

    /** @param array<string, string|null> $entries */
    public function dump(array $entries): string
    {
        return Yaml::dump($entries);
    }
}
```

```php
// In a service provider's boot() method:
$this->app->resolving(
    \Syriable\Translations\Storage\FormatRegistry::class,
    fn ($registry) => $registry->register(new YamlFormat()),
);
```

## Add a custom storage driver

Store the catalog somewhere other than the filesystem (a database, a remote
service) by implementing `Contracts\TranslationDriver` and registering a creator
on the `StorageManager`.

```php
use Syriable\Translations\Storage\StorageManager;

$this->app->afterResolving(StorageManager::class, function (StorageManager $manager) {
    $manager->extend('database', fn (array $config) => new DatabaseTranslationDriver($config));
});
```

```php
// config/translations.php
'storage' => [
    'default' => 'database',
    'drivers' => [
        'database' => ['driver' => 'database', 'connection' => 'mysql'],
        'file' => ['driver' => 'file', 'path' => lang_path()],
    ],
],
```

> Keep a `file` driver configured even when another driver is the default — the
> import and export commands use it as the canonical on-disk representation.

## Add a custom validation rule

Implement `Contracts\ValidationRule`.

```php
use Syriable\Translations\Contracts\ValidationRule;
use Syriable\Translations\Domain\Enums\IssueSeverity;
use Syriable\Translations\Domain\Locale;
use Syriable\Translations\Domain\Translation;
use Syriable\Translations\Validation\Issue;

final class NoTrailingSpaceRule implements ValidationRule
{
    public function id(): string
    {
        return 'no_trailing_space';
    }

    /** @return list<Issue> */
    public function validate(Translation $source, Translation $target, Locale $locale): array
    {
        if ((string) $target->value === rtrim((string) $target->value)) {
            return [];
        }

        return [new Issue(
            $this->id(),
            IssueSeverity::Warning,
            $source->key,
            $locale->code,
            'Translation has trailing whitespace.',
        )];
    }
}
```

```php
// config/translations.php
'validation' => [
    'rules' => [
        // ... built-in rules ...
        \App\Translations\NoTrailingSpaceRule::class,
    ],
],
```

Rules are resolved from the container and run against every translated value,
compared to its source.
