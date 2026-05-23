# Laravel Translations

[![tests](https://github.com/syriable/laravel-translations/actions/workflows/tests.yml/badge.svg)](https://github.com/syriable/laravel-translations/actions/workflows/tests.yml)
[![coding-standards](https://github.com/syriable/laravel-translations/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/syriable/laravel-translations/actions/workflows/coding-standards.yml)

> A backend-only Laravel package to **extract**, **manage**, **validate**, and
> **synchronize** your application's translations — from the command line and
> from code. No UI, no assets, no lock-in.

> **Status: `v0.1.0-beta`.** The architecture is stable and the core systems are
> production-quality, but the public API may still change while we gather
> ecosystem feedback. See [UPGRADE.md](UPGRADE.md).

## Vision

Most translation tooling forces a choice: a heavyweight dashboard you have to
host, or a pile of ad-hoc scripts. This package takes a third path — a clean,
composable, **backend-only** toolkit that treats your translation files as the
source of truth and gives you first-class CLI workflows and services to keep
them healthy.

- **Extraction** — find every translation usage in your Blade and PHP via real
  AST parsing, not fragile regular expressions.
- **Management** — read and write your catalog through a storage abstraction with
  pluggable drivers and file formats.
- **Synchronization** — reconcile the keys your code uses with the keys your
  catalog defines, with full dry-run support.
- **Validation** — catch placeholder, pluralization and markup mistakes before
  they ship.
- **Health** — report missing keys, unused keys and per-locale completeness, in
  a CI-friendly way.

## Installation

```bash
composer require syriable/laravel-translations
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=translations-config
```

**Requirements:** PHP 8.3+ and Laravel 11, 12 or 13.

## Quick start

```bash
# What translation keys does my code actually use?
php artisan translations:extract

# Create any keys used in code but missing from the catalog (dry run first).
php artisan translations:sync --dry-run
php artisan translations:sync

# Are my translations consistent with the source language?
php artisan translations:validate

# How complete is each locale, and what is unused?
php artisan translations:health
```

## Commands

| Command | Description |
| --- | --- |
| `translations:extract` | Scan source code and list every translation key in use. |
| `translations:sync` | Reconcile extracted keys with the catalog. Supports `--dry-run`, `--locale=`, `--prune`. |
| `translations:import` | Load the catalog from disk into the active storage driver. |
| `translations:export` | Write the catalog back to disk, normalized and sorted. |
| `translations:validate` | Run the validation pipeline against every locale. |
| `translations:health` | Report missing keys, unused keys and completeness per locale. |
| `translations:locales` | List discovered locales and their key counts. |

Every command returns a non-zero exit code on failure, so they compose cleanly
in CI pipelines.

## Architecture overview

The package is organised into clear domain boundaries:

```
Domain        Immutable value objects: TranslationKey, TranslationEntry,
              TranslationCatalog, Locale, SourceReference.
Extraction    Discovers key usages in source code (AST-based scanners).
Storage       Reads/writes the catalog through drivers and file formats.
Management    Import, export and synchronization services.
Analysis      Missing/unused/completeness health reporting.
Validation    Rule pipeline comparing translations to their source.
Console       Thin Artisan command layer over the services above.
Contracts     The extension points: Scanner, TranslationDriver, FileFormat,
              ValidationRule.
```

See [docs/architecture/overview.md](docs/architecture/overview.md) for a deeper
tour.

## Configuration

The published `config/translations.php` controls locales, the language path,
extraction paths and scanners, the storage driver, synchronization behaviour,
validation rules and analysis ignore patterns. Every option is documented inline.

## Extending

The package is extensible by contract — see
[docs/architecture/extending.md](docs/architecture/extending.md):

- **Custom scanner** — implement `Contracts\Scanner` to support a new file type.
- **Custom format** — implement `Contracts\FileFormat` to read/write e.g. YAML.
- **Custom driver** — implement `Contracts\TranslationDriver` to store the
  catalog somewhere other than the filesystem.
- **Custom validation rule** — implement `Contracts\ValidationRule`.

Register your class in the relevant `config/translations.php` array.

## Testing

```bash
composer test
```

## Roadmap

See the [CHANGELOG](CHANGELOG.md) and the "Suggested Next Milestones" section of
the release notes. Highlights on the path to `1.0`: a database storage driver,
YAML/PO formats, and an optional AI translation provider contract.

## Credits

- [Syriable](https://github.com/syriable)
- All contributors

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
