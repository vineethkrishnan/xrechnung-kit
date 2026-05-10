# Using xrechnung-kit with TYPO3

The TYPO3 extension wraps the framework-agnostic core in a TYPO3-aware service that you can inject anywhere TYPO3's Symfony DI reaches: extbase controllers, scheduler tasks, hooks, middleware.

::: tip Status
Scaffold. The package is installable, the public surface is settled, and the integration code (admin module, scheduler task, fluid templating helpers) will land as real consumers drive content into it. If you have a near-term TYPO3 use case for XRechnung, [open an issue](https://github.com/vineethkrishnan/xrechnung-kit/issues) describing the integration shape you need.
:::

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-typo3
```

In the TYPO3 backend, navigate to **Admin Tools -> Extensions** and activate **xrechnung-kit**.

Compatible with TYPO3 11.5 LTS, 12.4 LTS, and 13.x.

## Configure

The extension's services are registered via `Configuration/Services.yaml`. Override or replace any service with a project-local `Configuration/Services.yaml` in your sitepackage extension if you need to swap an implementation (for example, a custom `XRechnungValidator` subclass with project-specific Schematron rules).

## Generate an XRechnung

```php
namespace MyVendor\MySitepackage\Service;

use Vineethkrishnan\XrechnungKitTypo3\Service\XrechnungService;
use XrechnungKit\Mapping\MappingData;

final class InvoiceExportService
{
    public function __construct(
        private readonly XrechnungService $xrechnung,
    ) {
    }

    public function exportToFileadmin(MappingData $mapping, string $invoiceNumber): string
    {
        $target = sprintf(
            '%sxrechnung/%s.xml',
            \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/fileadmin/',
            $invoiceNumber,
        );

        return $this->xrechnung->generateAndValidate($mapping, $target);
    }
}
```

## Building MappingData from Extbase domain models

Bring your own mapper. Read [Mapping data contract](/mapping-data) for the public input shape, then write a `MappingData::standardInvoice(...)`-style factory that walks your Extbase models. The library is intentionally agnostic about your domain shape - it only validates the produced `MappingData`.

## KoSIT validation

When the optional `vineethkrishnan/xrechnung-kit-kosit-bundle` is installed and Java 17+ is available on the host, the adapter exposes a Schematron pass via the same service:

```php
$valid = $validator->validateSchematron($path);
```

A scheduler task wrapper (`Vineethkrishnan\XrechnungKitTypo3\Task\KositValidationTask`) will land once the scaffold has real content.

## Logging

`XrechnungService` accepts a PSR-3 logger. TYPO3's logging framework is PSR-3 compatible, so the default DI binding routes alerts to TYPO3's standard log writers. No extra configuration required.

## Testing

For integration tests, use TYPO3's own `typo3/testing-framework` and inject the service in a functional test as you would in production.

## See also

- [Mapping data contract](/mapping-data)
- [API overview](/reference/api)
- [Generated API reference](/api/)
- [KoSIT Schematron validation](/kosit-validation)
