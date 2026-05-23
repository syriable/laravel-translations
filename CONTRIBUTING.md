# Contributing

Contributions are welcome and appreciated. This document describes the workflow
and quality bar for the project.

## Workflow

1. Fork the repository and create a feature branch from `main`
   (e.g. `feature/yaml-format`, `fix/blade-nested-echo`).
2. Make focused, atomic commits using
   [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`,
   `refactor:`, `test:`, `docs:`, `chore:`, `perf:`).
3. Add or update tests for every behavioural change.
4. Ensure the full quality suite passes locally (see below).
5. Open a pull request describing the *why*, not just the *what*.

## Quality bar

Before opening a pull request, run:

```bash
composer lint      # Pint code style (dry run)
composer analyse   # PHPStan static analysis
composer test      # Pest test suite
```

All three must pass. CI runs the same checks across the supported PHP and
Laravel matrix.

## Design principles

- **Backend only.** No UI, no assets, no controllers. CLI and services only.
- **Single responsibility.** Prefer small, composable services over god classes.
- **Immutable domain.** Domain values are `readonly` DTOs.
- **Extensible by contract.** New scanners, formats, drivers and rules are added
  by implementing the relevant contract and registering it in configuration.
- **Strict types everywhere.** Every file declares `strict_types=1`.

## Reporting issues

Please include the package version, Laravel version, PHP version, and a minimal
reproduction. Security issues should be reported privately to the maintainer.
