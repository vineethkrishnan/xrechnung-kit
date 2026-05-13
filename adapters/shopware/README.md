# xrechnung-kit-shopware

Shopware 6 plugin that wires [`vinelabs-de/xrechnung-kit`](https://packagist.org/packages/vinelabs-de/xrechnung-kit) into Shopware's Symfony DI container, logging, and order workflow events.

This is the source under `adapters/shopware/` of the [xrechnung-kit](https://github.com/vineethkrishnan/xrechnung-kit) monorepo. It is published to Packagist as a standalone Shopware plugin via the auto-publish workflow.

## Install

In a Shopware 6 project:

```bash
composer require vineethkrishnan/xrechnung-kit-shopware
bin/console plugin:refresh
bin/console plugin:install --activate XrechnungKitShopware
```

Compatible with Shopware 6.5 and 6.6.

## Usage

Inject the service into a controller, subscriber, or order workflow handler:

```php
public function __construct(
    private readonly \Vineethkrishnan\XrechnungKitShopware\Service\XrechnungService $xrechnung,
) {}

$path = $this->xrechnung->generateAndValidate($mappingData, $targetPath);
```

Full integration guide: [xrechnung-kit.vineethnk.in/frameworks/shopware](https://xrechnung-kit.vineethnk.in/frameworks/shopware).

## Status

Tier 1 + Tier 2 implemented (Phases A through D from the project plan): plugin config, custom field set, DAL entity, order-state generation, admin SPA tab with regenerate / download / send-to-PEPPOL, quarantine list, audit, scheduled retry, admin notifications, PEPPOL delivery via configurable webhook. Reference PHPUnit tests in `tests/` and Cypress e2e specs in `cypress/`. See the project [docs](https://xrechnung-kit.vineethnk.in/frameworks/shopware) for the full integration guide.

## Shopware Store listing

The plugin is not on the Shopware Store today; the canonical install is `composer require vineethkrishnan/xrechnung-kit-shopware` from Packagist. The full Store-publishing path (prerequisites, code-quality gates, packaging, assets, free vs paid licensing, submission and update workflow) is captured in [STORE.md](STORE.md), along with the [`tools/store/build-shopware-zip.sh`](../../tools/store/build-shopware-zip.sh) build script that produces a Store-ready zip with a production `vendor/` bundled.

## License

MIT. See [LICENSE](https://github.com/vineethkrishnan/xrechnung-kit/blob/main/LICENSE).
