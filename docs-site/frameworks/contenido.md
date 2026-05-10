# Using xrechnung-kit with Contenido CMS

The Contenido plugin wraps the framework-agnostic core for use inside Contenido CMS sites. Contenido does not ship a Symfony-style DI container, so the package is a plain PHP service class you instantiate where you need it - typically inside a custom Contenido module's output PHP or a backend plugin entry point.

::: tip Status
Scaffold. The package is installable, the public surface is settled, and the integration code (Contenido module wrappers, backend CMS integration, scheduler hooks) will land as real consumers drive content into it. If you have a near-term Contenido use case for XRechnung, [open an issue](https://github.com/vineethkrishnan/xrechnung-kit/issues) describing the integration shape you need.
:::

## Install

Two-step install because Contenido lacks a standard Composer plugin type:

```bash
composer require vineethkrishnan/xrechnung-kit-contenido
ln -s vendor/vineethkrishnan/xrechnung-kit-contenido data/plugins/xrechnung_kit
```

In the Contenido backend, navigate to **System -> Plugins** and activate **xrechnung_kit**.

Compatible with Contenido 4.10 and newer.

## Generate an XRechnung from a module

```php
use Vineethkrishnan\XrechnungKitContenido\Service\XrechnungService;
use XrechnungKit\XRechnungValidator;

$service = new XrechnungService(new XRechnungValidator(), cRegistry::getLog());
$path = $service->generateAndValidate($mappingData, $targetPath);
```

`cRegistry::getLog()` returns Contenido's built-in PSR-3 logger. If you are running on an older Contenido that does not yet expose PSR-3, wrap your `cLog` in a thin PSR-3 adapter or use `\XrechnungKit\Logger\NullLogger` while you stand up the integration.

## Building MappingData from Contenido data

Bring your own mapper. Read [Mapping data contract](/mapping-data) for the public input shape. The library is intentionally agnostic about your domain shape - it only validates the produced `MappingData`. A reasonable Contenido pattern is:

1. Read order or invoice records from your Contenido client database via `cApiArticleLanguageCollection` or your project-specific repository class.
2. Build a `MappingData::standardInvoice(...)` (or one of the other named constructors) from those records.
3. Pass to the `XrechnungService::generateAndValidate(...)` call above.

## Where the file goes

The plugin does not pick a storage location. Pass the absolute path you want; the generator writes there atomically. Common targets:

- A subfolder of Contenido's `data/upload/` for client-visible exports
- A project-private folder outside the document root for archive-only invoices

## Public-administration buyers

For B2G orders, the buyer's `BuyerReference` field carries the Leitweg-ID. Capture it in your invoice form or import pipeline, then pass it into `Party::publicAdministration(leitwegId: ...)` when building the mapping. See [Glossary (DE)](/glossary-de) for context.

## KoSIT validation

When `vineethkrishnan/xrechnung-kit-kosit-bundle` is installed and Java 17+ is available on the host, the validator exposes a Schematron pass:

```php
$valid = $validator->validateSchematron($path);
```

## See also

- [Mapping data contract](/mapping-data)
- [Document type codes](/reference/document-types)
- [Extending xrechnung-kit](/extending) - how to plug in custom channels, loggers, validators, and mappers
- [API overview](/reference/api)
- [Generated API reference](/api/)
- [KoSIT Schematron validation](/kosit-validation)
