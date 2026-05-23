# Architecture Overview

`syriable/laravel-translations` is a backend-only toolkit. It has no controllers,
no routes, no assets — just a small set of focused services exposed through
Artisan commands and the service container. This document explains how those
pieces fit together.

## Layered design

The package is organised into clear, dependency-directed layers. Higher layers
depend on lower layers, never the other way around.

```
┌──────────────────────────────────────────────────────────────┐
│ Console            Thin Artisan commands. No business logic.   │
├──────────────────────────────────────────────────────────────┤
│ Application        Extractor, Synchronizer, CatalogTransfer,    │
│                    ValidationPipeline, HealthAnalyzer.          │
├──────────────────────────────────────────────────────────────┤
│ Infrastructure     Storage drivers, file formats, AST parser,   │
│                    file finder.                                  │
├──────────────────────────────────────────────────────────────┤
│ Domain             Immutable value objects: TranslationKey,     │
│                    Translation, LocaleCatalog, Catalog, Locale.  │
├──────────────────────────────────────────────────────────────┤
│ Contracts          Scanner, TranslationDriver, FileFormat,      │
│                    ValidationRule — the extension seams.         │
└──────────────────────────────────────────────────────────────┘
```

The **domain** never depends on Laravel. The value objects are plain PHP and can
be unit-tested without booting an application — which is exactly how the unit
suite exercises them.

## The two sides of a translation system

The package models translations as two distinct concerns that meet in the
analysis and synchronization layers:

- **Usage** — what keys does the *code* reference? Discovered by the
  **Extraction** subsystem from Blade and PHP source.
- **Definition** — what keys does the *catalog* define, and what are their
  values per locale? Read and written by the **Storage** subsystem.

```
   source code ──▶ Extraction ──▶ ExtractionResult ─┐
                                                     ├─▶ HealthAnalyzer ──▶ HealthReport
   lang files  ──▶ Storage    ──▶ Catalog ──────────┘
                                       │
                                       ├─▶ Synchronizer    (reconcile usage ⇄ definition)
                                       ├─▶ ValidationPipeline (compare target ⇄ source)
                                       └─▶ CatalogTransfer (move between drivers)
```

## Extraction flow

1. `FileFinder` streams candidate files from the configured paths, skipping
   excluded directories and matching scanner extensions (longest first, so
   `blade.php` beats `php`).
2. The `Extractor` hands each file to the `Scanner` that owns its extension.
3. `PhpScanner` parses the file with `nikic/php-parser`; `BladeScanner` first
   rewrites the template into an equivalent PHP template (preserving line
   numbers) and then reuses the same `AstKeyExtractor`.
4. A `TranslationCallVisitor` walks the AST and records every call to a
   translation helper (`__`, `trans`, `trans_choice`, plus `@lang`/`@choice`
   in Blade) whose first argument is a **literal** string. Dynamic keys are
   ignored on purpose — they cannot be resolved statically.
5. Results are aggregated by key into an immutable `ExtractionResult`, each key
   carrying every `SourceReference` (file + line) where it appears.

Using a real AST instead of regular expressions means multi-line calls,
comments, escaped quotes and nested expressions are handled correctly.

## Storage flow

The `StorageManager` resolves a `TranslationDriver` from configuration. The
default `file` driver maps the catalog to Laravel's language files:

- `lang/{locale}/{group}.php` ⇄ keys like `group.item`
- `lang/{locale}.json` ⇄ keys-as-sentences
- `lang/vendor/{namespace}/{locale}/{group}.php` ⇄ keys like `namespace::group.item`

PHP files are parsed by **evaluating their `return` expression through the AST
constant evaluator** rather than `require`-ing them, so reading a catalog never
executes project code. Writing routes each key back to the right file through the
`KeyRouter` and renders it with the matching `FileFormat`.

## Application services

- **Synchronizer** reconciles an `ExtractionResult` with the catalog: it fills
  keys used in code but missing from a locale and, optionally, prunes keys that
  are no longer referenced. It is dry-run aware.
- **ValidationPipeline** runs each configured `ValidationRule` over every
  translated value, comparing it to the source value, and collects `Issue`s.
- **HealthAnalyzer** produces a `HealthReport`: missing keys, unused keys and
  per-locale completeness.
- **CatalogTransfer** copies a catalog from one driver to another, powering
  import and export.

## Design principles

- **Backend only.** No UI, no HTTP layer, no published assets.
- **Immutable domain.** Value objects are `readonly`; aggregates expose explicit
  mutation methods.
- **Extensible by contract.** Every variable behaviour is a contract resolved
  from configuration and the container.
- **Strict types everywhere** and PHPStan level 6.
- **CI-friendly.** Commands return meaningful exit codes.

## Beta limitations

See the "Known Beta Limitations" section of the release notes and the
[CHANGELOG](../../CHANGELOG.md). The most relevant today:

- Only the `file` storage driver ships in `0.1.0-beta`; the driver contract is
  stable so a database driver can be added without API changes.
- Extraction recognises literal-string keys only; dynamic keys must be covered
  with `analysis.ignore` patterns so they are not reported as unused.
