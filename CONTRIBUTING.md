# Contributing

Thanks for contributing! A few notes to keep changes consistent with the rest of the package.

## Getting started

```bash
composer install
composer test        # vendor/bin/pest
vendor/bin/pint      # code style (Laravel preset)
```

## Conventions

- **Backend-only.** No HTTP controllers, routes, form requests, or view/frontend code.
- **Stay flat.** Follow the existing structure — small service classes, one job per class, no
  Domain/Application/Infrastructure layering. Prefer configuration over new abstractions.
- **Types everywhere.** Explicit return types and parameter types on every method; PHP 8 constructor
  property promotion.
- **No comments or PHPDoc** unless a constraint genuinely can't be expressed in code; the code should
  read on its own. Prefer early returns over nesting and keep functions small.
- **Test every behaviour change.** Feature tests live in `tests/Feature`, unit tests in `tests/Unit`.
  AI paths are tested through the `Translator` contract with `FakeTranslator` — no live calls.

## Pull requests

- Run `composer test` and `vendor/bin/pint --test` before opening a PR. CI runs both across
  PHP 8.2–8.4 and Laravel 11–12.
- Keep PRs focused. Describe the behaviour change and link any related issue.
