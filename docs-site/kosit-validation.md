# KoSIT Schematron validation

UBL XSD validation runs in-memory inside `XRechnungGenerator`. It is a structural floor. KoSIT Schematron scenarios add the German federal business rules (BR-DE-*, CIUS rules) and is what ZRE / OZG-RE / PEPPOL endpoints actually run before they accept your invoice.

This guide covers running KoSIT locally, in Docker, and in CI.

## What you need

- Java 17+
- The `vineethkrishnan/xrechnung-kit-kosit-bundle` package installed (it ships the validator JAR + pinned scenarios as composer-managed package data)

## Install the bundle

```bash
composer require --dev vineethkrishnan/xrechnung-kit-kosit-bundle
```

The bundle ships the validator JAR and scenarios; nothing is downloaded at runtime.

## Local

```bash
composer kosit
```

Or invoke the CLI directly:

```bash
./vendor/bin/validate-kosit ./out/Invoice_RE-1.xml
./vendor/bin/validate-kosit corpus ./tests/Fixtures
```

The CLI exits non-zero on any failure. The HTML report is written next to the XML as `*.report.html` (covered by `.gitignore`).

## Cache directory

The bundle and scenarios live under a cache directory, resolved in this order:

1. `XRECHNUNG_KIT_CACHE_DIR` environment variable
2. `XDG_CACHE_HOME/xrechnung-kit`
3. `~/.cache/xrechnung-kit`

You can move the cache anywhere by setting the env var. CI uses the workspace path so the cache survives between cache restores.

## Docker

A reproducible local environment without installing Java on the host:

```bash
docker run --rm \
  -v "$PWD":/work \
  -w /work \
  eclipse-temurin:17-jre \
  ./vendor/bin/validate-kosit corpus ./tests/Fixtures
```

`composer install` is still required on the host (or in a separate PHP container) before this runs.

## CI

The repo's `.github/workflows/test.yml` includes a `KoSIT Schematron` job that:

1. Sets up PHP 8.3 and Java 17 (Temurin).
2. Runs `composer install`.
3. Restores the KoSIT cache from a previous run, keyed by `kosit-bundle/composer.json` and `composer.lock`.
4. Runs `composer kosit` against the fixture corpus with `XRECHNUNG_KIT_CACHE_DIR` pointing at the workspace.

Failure is a hard merge block.

## Exit codes

| Code | Meaning |
|---|---|
| 0 | All fixtures KoSIT-strict valid |
| 1 | One or more fixtures failed validation (HTML report written) |
| 2 | Validator JAR could not be located (bundle not installed?) |
| 3 | Java not found on PATH |
| 4 | Cache directory not writable |

## Pinning policy

Scenarios are pinned in `kosit-bundle/composer.json`. A bump:

- That preserves pass / fail equivalence for our fixture corpus -> ships in a minor.
- That changes pass / fail behaviour for any fixture -> ships in a major.
- That changes emitted XML expectations -> ships in a major across the whole `vineethkrishnan/xrechnung-kit-*` family.

See [policies.md](policies.md#kosit-scenarios) for the full policy.

## Reporting a regression

If a fixture that used to pass now fails, open a bug report with:

- The fixture (or a minimal reduction)
- The HTML report (`*.report.html`)
- The bundle version (`composer show vineethkrishnan/xrechnung-kit-kosit-bundle`)
- The KoSIT scenarios version recorded in the bundle README
