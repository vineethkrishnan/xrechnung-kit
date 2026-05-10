# Using xrechnung-kit with WordPress

The WordPress plugin generates KoSIT-strict valid XRechnung 3.0 invoices from WordPress and WooCommerce orders. The plugin handles the WordPress-side wiring; the framework-agnostic core handles the actual generation, in-memory XSD validation, atomic writing, and (optionally) KoSIT Schematron validation.

::: tip Status
Scaffold. The package is installable on Composer-managed WordPress sites, bootstraps cleanly, and exposes the public class surface. The WooCommerce order-to-MappingData mapper, the admin settings page, and the scheduled queue handler are deliberately left out so the package shape can settle before a real consumer drives content into it. If you have a near-term WordPress / WooCommerce use case for XRechnung, [open an issue](https://github.com/vineethkrishnan/xrechnung-kit/issues) describing the integration shape you need.
:::

## Install

### Composer-managed sites (Bedrock, Trellis, custom)

```bash
composer require vineethkrishnan/xrechnung-kit-wordpress
```

`composer/installers` routes the package to `wp-content/plugins/xrechnung-kit/`. Activate from the WordPress admin under **Plugins -> Installed plugins**, or via WP-CLI:

```bash
wp plugin activate xrechnung-kit
```

### Standalone download

For sites that are not Composer-managed, a standalone zip with a bundled `vendor/` will be published with each tagged release once the scaffold has real content. Until then, install via Composer or open an issue.

## Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- WordPress 6.0 or newer
- WooCommerce (optional; required only for the WooCommerce order mapper when that lands)
- Java 17+ on the host if KoSIT Schematron validation is enabled

## Generate an XRechnung

In any plugin or theme, build a `MappingData` and pass it to the validator and generator. Until the scheduled-queue and admin settings handlers land, generation is fully manual:

```php
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

add_action('woocommerce_order_status_completed', function (int $orderId): void {
    $order = wc_get_order($orderId);
    $mapping = my_order_to_mapping_data($order);

    $upload = wp_upload_dir();
    $target = sprintf('%s/xrechnung/%s.xml', $upload['basedir'], $order->get_order_number());

    $entity = XRechnungBuilder::buildEntity($mapping);
    $path = (new XRechnungGenerator($entity))->generateXRechnung($target);

    $validator = new XRechnungValidator();
    if (!$validator->validate($path)) {
        error_log('xrechnung-kit: file landed at *_invalid.xml: ' . $path);
    }
});
```

`my_order_to_mapping_data($order)` is a function you write against your shop's customer / billing conventions. Read [Mapping data contract](/mapping-data) for the public input shape.

## Where the file goes

The plugin does not pick a storage location. Pass the absolute path you want; the generator writes there atomically. The most common pattern is `wp_upload_dir()` plus a project-specific subfolder. Make sure the path is **outside** the WordPress media library if the invoice is not meant to be publicly downloadable.

## Public-administration buyers

For B2G orders, the buyer's `BuyerReference` field carries the Leitweg-ID. Capture it as a WooCommerce custom field at checkout, then read it from order meta in your mapper into `Party::publicAdministration(leitwegId: ...)`. See [Glossary (DE)](/glossary-de) for context on Leitweg-IDs and which document type codes apply.

## Settings

A settings page under **Settings -> xrechnung-kit** will land in a follow-up. The intended options:

- Default seller party (name, address, tax ID)
- Storage path for generated files
- KoSIT scenario to run (or disabled)
- WooCommerce order statuses that trigger generation

For now, configure these in your own plugin or `wp-config.php`.

## KoSIT validation

When `vineethkrishnan/xrechnung-kit-kosit-bundle` is installed and Java 17+ is available on the host, the validator exposes a Schematron pass:

```php
$valid = $validator->validateSchematron($path);
```

## See also

- [Mapping data contract](/mapping-data)
- [Document type codes](/reference/document-types)
- [API overview](/reference/api)
- [Generated API reference](/api/)
- [KoSIT Schematron validation](/kosit-validation)
