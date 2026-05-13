# xrechnung-kit-typo3

TYPO3 extension that wires [`vinelabs-de/xrechnung-kit`](https://packagist.org/packages/vinelabs-de/xrechnung-kit) into TYPO3's Symfony DI container, logging, and scheduler.

This is the source under `adapters/typo3/` of the [xrechnung-kit](https://github.com/vineethkrishnan/xrechnung-kit) monorepo. It is published to Packagist as a standalone TYPO3 extension via the auto-publish workflow.

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-typo3
```

## Usage

Inject the service into a controller or scheduler task:

```php
public function __construct(
    private readonly \Vineethkrishnan\XrechnungKitTypo3\Service\XrechnungService $xrechnung,
) {}

$path = $this->xrechnung->generateAndValidate($mappingData, $targetPath);
```

Full integration guide: [xrechnung-kit.vineethnk.in/frameworks/typo3](https://xrechnung-kit.vineethnk.in/frameworks/typo3).

## Status

Pre-alpha scaffold. Public API is stable, implementation will fill in as real consumer use cases drive content. Open an issue describing your TYPO3 use case and we will prioritise.

## License

MIT. See [LICENSE](https://github.com/vineethkrishnan/xrechnung-kit/blob/main/LICENSE).
