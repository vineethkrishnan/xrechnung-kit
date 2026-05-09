# Examples

Runnable example scripts that exercise xrechnung-kit end-to-end. Each example is self-contained: install the demonstrated package, run the script, get a file on disk.

## Layout

```
examples/
  standalone/      # plain PHP, no framework
  laravel/         # minimal Laravel app skeleton
  symfony/         # minimal Symfony app skeleton
  cakephp/         # minimal CakePHP app skeleton
  laminas/         # minimal Laminas MVC app skeleton
```

## Running an example

```bash
cd examples/standalone
composer install
php generate.php
```

Each example writes its output XML to `out/` next to the script and prints the resolved path on success. KoSIT-strict validation is opt-in per example via the `--kosit` flag (requires the kosit-bundle and Java 17+).

## Convention

Every example must:

- Run from a clean checkout via `composer install` + the documented entry-point command.
- Produce a KoSIT-strict valid XRechnung 3.0 XML for at least one document type.
- Print the path to the generated file (and the validation result) on success.
- Exit non-zero on any pipeline failure.

## Status

Stub. Examples will land alongside the source extraction (checklist item A1). Each example has a placeholder `README.md` describing the intended demonstration; actual scripts are added as the corresponding adapter packages become testable.
