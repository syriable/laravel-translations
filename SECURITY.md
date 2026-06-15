# Security Policy

## Reporting a vulnerability

Please report security issues privately by email to **alkhatib.syr@gmail.com** rather than opening a
public issue. You'll get an acknowledgement within a few business days, and we'll coordinate a fix
and disclosure timeline with you.

## Supported versions

The package is pre-1.0; security fixes target the latest `main`.

## Trust model

This is a backend-only toolkit with no HTTP layer or authentication of its own. A few behaviours are
worth understanding before you wire it into an application:

- **Importing PHP lang files executes them.** `translations:import` loads `.php` language files with
  `require` — exactly how Laravel core loads array lang files. Only import language directories you
  trust; a writable `lang/` directory or a compromised package shipping `vendor/.../lang/*.php` would
  run arbitrary code at import time. Set `import.scan_vendor` to `false` if you don't want vendor lang
  files imported.
- **Lang and scan paths are trusted input.** `lang_path`, `scanning.paths`, and the `--path` options
  are taken from configuration/CLI and used as-is. Don't pass attacker-controlled paths to
  import/scan.
- **Models have mass-assignment protection disabled.** Every model uses `$guarded = []`. The package
  ships no controllers, so this isn't exploitable by the package itself — but because it's meant to be
  wrapped by your own UI, you must validate and whitelist input before passing it to
  `create()`/`update()`/`fill()`. Columns such as `status`, `is_source`, `reviewed_by` and
  `ai_generated` are otherwise freely assignable.
- **History and AI prompts store values in plaintext.** Revisions (`tx_revisions`), usage snippets
  (`tx_phrase_usages`) and AI prompts include translation values verbatim. Don't embed secrets
  (tokens, PII) in translation strings. Prune revision history with `translations:prune-revisions`;
  usages are reconciled on every `translations:scan-usage` and cascade-deleted with their phrase.
- **AI translation output is untrusted.** Prompts fence untrusted context (glossary, notes, usages)
  so it can't act as instructions, but the model's *output* is still untrusted text. Escape it on
  render in the consuming application; if rendered as HTML, treat it as any other user-supplied HTML.
- **Authorization is your responsibility.** The package has no auth layer. Review and approval actions
  accept a free-form actor string that is advisory only — it is recorded, not enforced. Enforce who
  may translate, review or manage in your application using `MemberRole` (which exposes
  `canTranslate()`, `canReview()`, `canManage()`) or your own gates/policies.
