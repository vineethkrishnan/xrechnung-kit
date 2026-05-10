# Publishing to the Shopware Store

The Shopware adapter ships today as `vineethkrishnan/xrechnung-kit-shopware` on Packagist, MIT licensed, installed via `composer require`. A Shopware Store listing is on the roadmap but not yet active. This doc captures everything required to flip that switch later, in the order it has to happen.

The intent: keep the Composer / Packagist path as the canonical install for developers and CI, and let the Store listing reach merchants who manage plugins from inside the Shopware admin. Both paths consume the same source under `adapters/shopware/`.

## Status today

| Item | State |
|---|---|
| Source location | `adapters/shopware/` in the monorepo |
| Composer / Packagist | Live: `composer require vineethkrishnan/xrechnung-kit-shopware` |
| License | MIT |
| Plugin label / description | Set in `composer.json` `extra.label` and `extra.description` (en + de) |
| Manufacturer link | `extra.manufacturerLink` set to `https://xrechnung-kit.vineethnk.in/` |
| Plugin class | `extra.shopware-plugin-class` set, plugin loads cleanly when installed |
| Custom field set + DAL entity + migrations | Phase A |
| Order-state subscriber + mapper | Phase B |
| Admin SPA tab + quarantine list | Phase C, D |
| Audit + retry + admin notifications | Phase D |
| PEPPOL delivery | Phase 5, vendor-neutral webhook |
| Backend + Cypress tests | Phase 6 |
| Shopware Store listing | not started |

## Prerequisites for the Store path

1. **Shopware Account** at https://account.shopware.com/. Free for partners.
2. **Manufacturer profile** linked to that account. Carries the public name shown on the Store, the legal address, the support email, and (for paid plugins) the bank details for payouts.
3. **Partner agreement** signed in the partner area. Different agreements for free plugins and paid plugins; paid requires extra steps because Shopware GmbH handles invoicing.
4. **Cooperative Tool (SCT)** access. The SCT is the Shopware-side automated checker that every plugin submission must pass.

## Code-quality gates the Store runs

The SCT enforces a set of static checks before a plugin can even be reviewed:

- **PHPStan** at level 8 against `src/`. Plugin code that fails stricter levels can still pass at 8 but the Store may flag it during manual review.
- **PHP-CS-Fixer** with the Symfony rule-set. Coding-style violations are auto-fixed by Shopware's pipeline; we ship the same config so the diff is empty by the time we submit.
- **Plugin manifest validation** against the Shopware schema (label/description in en + de, version, valid plugin class, valid composer dependency range).
- **Twig template linting** for the admin SPA templates.
- **Vue / JavaScript linting** for the admin SPA source.
- **Compatibility check** against every Shopware platform version the manifest declares.

Action items before first submission:

- Add `vendor/bin/phpstan analyse src --level=8` to the Shopware-tests CI workflow.
- Add `vendor/bin/php-cs-fixer fix src --dry-run --diff` to CI.
- Wire `vendor/bin/shopware-plugin-validator` (community tool that mirrors SCT locally) into a pre-submission step.

## Packaging the plugin for the Store

The Store does **not** run `composer install` on submitted plugins. The zip must include a runnable `vendor/` directory.

The recommended build steps (lifted into `tools/store/build-shopware-zip.sh`):

```bash
# Clean working copy
rm -rf .build adapters/shopware/vendor

# Production-only composer install scoped to the adapter
composer install --working-dir=adapters/shopware --no-dev --optimize-autoloader --no-progress

# Build the admin SPA (Shopware projects ship the JS pre-built; the Store
# accepts pre-built bundles or does a build itself depending on plugin
# type, but pre-built is faster and reproducible)
# This step requires a Shopware platform checkout to invoke its build
# pipeline; document the path in tools/store/README.md.

# Stage the files the Store needs
mkdir -p .build/XrechnungKitShopware
rsync -a --delete \
  --exclude tests \
  --exclude cypress \
  --exclude cypress.config.js \
  --exclude phpunit.xml.dist \
  --exclude STORE.md \
  --exclude composer.lock \
  adapters/shopware/ .build/XrechnungKitShopware/

# Zip
cd .build && zip -r ../XrechnungKitShopware-${VERSION}.zip XrechnungKitShopware
```

Key exclusions:

- `tests/` and `cypress/` - never useful at runtime, bloat the package
- `STORE.md`, `cypress.config.js`, `phpunit.xml.dist` - tooling artefacts
- `composer.lock` - the Store does not consume it
- Hidden files (`.git*`, `.idea`, `.vscode`)

## Required Store-listing assets

Beyond the code, the Store wants:

- **Plugin icon** at 256x256 px PNG. Lives at `Resources/store/plugin.png` (currently missing - placeholder needed). Must be original, not the federal eagle, not the KoSIT logo, not the official XRechnung wordmark. The xrechnung-kit monogram in `docs-site/public/logo.svg` is fine to reuse with PNG export.
- **Screenshots** of the plugin in action: at least three, 1280x800 px. Recommended set: the plugin config form, the order detail XRechnung tab in `generated` status, the quarantine list. Cypress can capture these reproducibly.
- **English description** explaining what the plugin does, who it is for, and what it requires. Cap roughly 2000 characters.
- **German description** of the same. Mandatory for the German Shopware Store.
- **Versioning notes** for each release. Map cleanly to the `CHANGELOG.md` generated by release-please at the repo root.

## Free vs paid licensing

For the first listing the recommendation is **free**, MIT, no Store license enforcement. Reasons:

- The project is independent open source and the README already commits to MIT.
- Shopware's license enforcement only activates for paid Store plugins; opting in adds support obligations.
- Merchants who want the plugin can already get it via Composer at zero cost; the Store listing serves discoverability, not monetisation.

If a paid version is ever desired (e.g., a "supported" tier with onboarding), the cleanest split is a separate Store-only Pro plugin that depends on the open-source core. The xrechnung-kit core stays MIT.

## Verification and signing

Shopware's signing process is opaque and handled in the partner area:

1. Upload the zip.
2. SCT runs automated checks. Failures come back with a structured report.
3. For paid plugins, manual review by the Shopware team. Free plugins can skip manual review for fast updates.
4. On pass, Shopware signs the plugin and publishes it to the Store. The signature is what merchants' Shopware installations verify on install.

We do not handle the signing keys ourselves; Shopware does.

## Submission and update workflow

Initial submission, free plugin path:

1. Build the zip via `tools/store/build-shopware-zip.sh`.
2. Upload via the partner area, choose the visibility, fill in metadata (icon, screenshots, en + de descriptions, supported Shopware versions).
3. Run SCT, fix any findings, re-upload.
4. Publish.

Update workflow:

1. Tag a new release in the monorepo (release-please handles the version bump).
2. Build a fresh zip with the new version.
3. Upload as a new version in the partner area. Shopware tracks per-version SCT results.
4. Publish the new version. Merchants see the upgrade prompt in the Shopware admin.

## What we explicitly do NOT do for the Store

- **No Store-only feature gates**: the open-source build and the Store build are byte-equivalent. No "Pro features" hidden behind a flag.
- **No telemetry**: the kit is committed to zero runtime network calls; the Store version inherits this.
- **No bundled commercial PDF library**: hybrid invoices live in the separate `xrechnung-kit-pdfa` package which itself is library-agnostic.

## Open items before the first submission

- [ ] Add the Store-listing assets at `Resources/store/` (icon + screenshots).
- [ ] Add PHPStan + PHP-CS-Fixer to the shopware-tests CI workflow.
- [ ] Wire `tools/store/build-shopware-zip.sh` into a release-time job.
- [ ] Draft the en + de Store descriptions (separate doc; sits with the project repo, gets pasted into the partner area at submission time).
- [ ] Decide whether the first release is `0.x` or `1.0.0` for Store purposes; merchants tend to install only 1.x+ from the Store.

These are tracked in the project README's roadmap; this document explains what each item entails.
