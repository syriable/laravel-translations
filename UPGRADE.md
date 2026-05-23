# Upgrade Guide

This document describes how to upgrade between major and minor versions of
`syriable/laravel-translations`.

While the package is in the `0.x` beta line, minor releases may contain breaking
changes. Each breaking change will be documented here with a clear migration
path before the `1.0.0` stable release.

## Versioning policy

- `0.x` (beta): the public API may change between minor versions. Breaking
  changes are listed below and announced in the [CHANGELOG](CHANGELOG.md).
- `>= 1.0` (stable): the package follows [Semantic Versioning](https://semver.org).
  Breaking changes only happen in major versions.

## From nothing to 0.1.0-beta

This is the first release. Install and publish the configuration:

```bash
composer require syriable/laravel-translations:^0.1.0-beta
php artisan vendor:publish --tag=translations-config
```

There is nothing to migrate.
