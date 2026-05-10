# Shopware adapter test suite

Two test layers, run independently:

| Layer | Tool | Where to run | Needs Shopware kernel |
|---|---|---|---|
| Unit | PHPUnit | anywhere with Composer + PHP 8.1+ | no |
| Integration | PHPUnit | inside a Shopware project that has the plugin installed | yes |
| Frontend e2e | Cypress | against a running Shopware admin with the plugin activated | yes |

## Unit tests

Run from the adapter directory:

```bash
composer install
vendor/bin/phpunit --testsuite Unit
```

Covers `PluginConfig`, `PeppolEnvelope`, `PeppolDeliveryResult`, and `WebhookPeppolDelivery` (mocked HTTP). All Shopware-side classes are mocked via PHPUnit; no kernel boot.

## Integration tests

These exercise the plugin install lifecycle, the order-state-to-generation flow, the manual regenerate endpoint, the scheduled retry handler, and the PEPPOL delivery service against a real Shopware DAL and database. Setup:

1. Install Shopware locally (`shopware-cli project create-project`).
2. Symlink or `composer require` the plugin into the Shopware project.
3. Activate the plugin: `bin/console plugin:install --activate XrechnungKitShopware`.
4. From the plugin directory run: `vendor/bin/phpunit --testsuite Integration`.

The integration tests skip themselves when run outside a Shopware kernel (the `IntegrationTestBehaviour` trait check). The skeletons under `tests/Integration/` describe the scenarios; flesh them out per the patterns in your Shopware platform repo's `tests/integration` examples.

## Frontend e2e (Cypress)

Cypress drives the Shopware admin SPA against a real running instance.

```bash
# from the repo root
npm install                       # installs cypress as a devDependency
cd adapters/shopware
CYPRESS_BASE_URL=http://localhost:8000 \
CYPRESS_ADMIN_USER=admin \
CYPRESS_ADMIN_PASSWORD=shopware \
npx cypress run
```

The specs:

- `cypress/e2e/order-detail-xrechnung.cy.js`: renders the tab, exercises regenerate / download / send-to-PEPPOL endpoints. Negative-case tolerant: if no order or no generated invoice exists in the test data, the relevant assertion is logged-and-skipped rather than failed
- `cypress/e2e/quarantine-list.cy.js`: navigates to the quarantine view and asserts on either the empty state or the data grid based on actual DB content

Cypress logs in via the admin OAuth token endpoint (`POST /api/oauth/token`) and stores the bearer token; subsequent requests go straight to the admin API.

## CI

The `shopware-tests.yml` workflow runs the unit tests on every push to `main` and on PRs touching `adapters/shopware/**`. Integration and Cypress jobs are stubbed (need a Shopware container in CI) and currently skip; turn them on once the runner has Shopware bootstrap.
