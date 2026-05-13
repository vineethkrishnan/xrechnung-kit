# xrechnung-kit-contenido

Contenido CMS plugin that wraps [`vinelabs-de/xrechnung-kit`](https://packagist.org/packages/vinelabs-de/xrechnung-kit) for generating KoSIT-strict valid XRechnung 3.0 invoices from Contenido content and order modules.

This is the source under `adapters/contenido/` of the [xrechnung-kit](https://github.com/vineethkrishnan/xrechnung-kit) monorepo. Published to Packagist as a standalone Contenido plugin via the auto-publish workflow.

## Install

Contenido does not have a standard Composer plugin type, so the install path is two steps:

1. Add the Composer package to a `vendor/` reachable from your Contenido site (often `data/vendor/` or a project-level `composer.json` next to the Contenido root):

   ```bash
   composer require vineethkrishnan/xrechnung-kit-contenido
   ```

2. Symlink or copy the plugin source into Contenido's plugin directory so Contenido's plugin discovery picks up the `plugin.xml`:

   ```bash
   ln -s vendor/vineethkrishnan/xrechnung-kit-contenido data/plugins/xrechnung_kit
   ```

3. In the Contenido backend, navigate to **System -> Plugins** and activate **xrechnung_kit**.

Compatible with Contenido 4.10 and newer.

## Usage

The package ships a plain PHP service class. Instantiate it where you need to generate an XRechnung:

```php
use Vineethkrishnan\XrechnungKitContenido\Service\XrechnungService;
use XrechnungKit\XRechnungValidator;

$service = new XrechnungService(new XRechnungValidator(), $logger);
$path = $service->generateAndValidate($mappingData, $targetPath);
```

`$logger` is any PSR-3 compatible logger; Contenido's bundled logger qualifies.

Full integration guide: [xrechnung-kit.vineethnk.in/frameworks/contenido](https://xrechnung-kit.vineethnk.in/frameworks/contenido).

## Status

Pre-alpha scaffold. The package is installable, the public surface is settled, and the integration code (Contenido module wrappers, backend CMS integration, scheduler hooks) will land as real consumers drive content into it. If you have a near-term Contenido use case for XRechnung, [open an issue](https://github.com/vineethkrishnan/xrechnung-kit/issues) describing the integration shape you need.

## License

MIT. See [LICENSE](https://github.com/vineethkrishnan/xrechnung-kit/blob/main/LICENSE).
