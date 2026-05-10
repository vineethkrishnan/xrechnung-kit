# xrechnung-kit-shopware

Shopware 6 plugin that wires [`vineethkrishnan/xrechnung-kit-core`](https://packagist.org/packages/vineethkrishnan/xrechnung-kit-core) into Shopware's Symfony DI container, logging, and order workflow events.

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

Pre-alpha scaffold. Public API is stable, the order-to-MappingData mapper and order-state subscriber are deliberately left out so the package surface can settle before a real consumer drives content into it. Open an issue describing your Shopware use case and we will prioritise.

## License

MIT. See [LICENSE](https://github.com/vineethkrishnan/xrechnung-kit/blob/main/LICENSE).
