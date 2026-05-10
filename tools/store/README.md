# Shopware Store packaging

Tooling for the future Shopware Store listing of `xrechnung-kit-shopware`.

The plugin is not on the Store today; the canonical install is `composer require vineethkrishnan/xrechnung-kit-shopware` from Packagist. This directory holds the build script and supporting docs for the day a Store listing is opened.

## Files

- `build-shopware-zip.sh` - reproducible build that produces a zip with a production `vendor/` bundled, ready for upload to the Shopware partner area
- See [adapters/shopware/STORE.md](../../adapters/shopware/STORE.md) for the full publishing workflow, prerequisites, code-quality gates, and asset requirements

## Building locally

```bash
bash tools/store/build-shopware-zip.sh           # uses version from composer.json
bash tools/store/build-shopware-zip.sh 0.2.0     # explicit version override
```

Output lands in `.build/XrechnungKitShopware-${VERSION}.zip`.

## CI integration

The Store-zip build is not wired into a workflow yet; once we open the Store listing, add a job to `shopware-tests.yml` (or a new `shopware-release.yml`) that runs the script on every `v*.*.*` tag and uploads the resulting zip as a release asset.
