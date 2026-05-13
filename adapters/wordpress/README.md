# xrechnung-kit-wordpress

WordPress plugin that generates KoSIT-strict valid XRechnung 3.0 / EN 16931 invoices from WordPress / WooCommerce orders. Wraps [`vinelabs-de/xrechnung-kit`](https://packagist.org/packages/vinelabs-de/xrechnung-kit).

This is the source under `adapters/wordpress/` of the [xrechnung-kit](https://github.com/vineethkrishnan/xrechnung-kit) monorepo. It is published to Packagist as a standalone WordPress plugin via the auto-publish workflow.

## Install

### Composer-managed sites (Bedrock, Trellis, custom)

```bash
composer require vineethkrishnan/xrechnung-kit-wordpress
```

`composer/installers` routes the package to `wp-content/plugins/xrechnung-kit/`. Activate from the WordPress admin or via WP-CLI:

```bash
wp plugin activate xrechnung-kit
```

### Standalone download

For sites not managed by Composer, a standalone zip with a bundled `vendor/` will be published with each tagged release once the scaffold has real content. Until then, install via Composer or open an issue.

## Status

Pre-alpha scaffold. The plugin bootstraps cleanly on a fresh WordPress install and exposes the public class surface, but the WooCommerce order-to-MappingData mapper, the admin settings page, and the scheduled queue handler are deliberately left out so the package shape can settle before a real consumer drives content into it.

If you have a near-term WordPress / WooCommerce use case for XRechnung, [open an issue](https://github.com/vineethkrishnan/xrechnung-kit/issues) describing the integration shape you need.

## Documentation

Full integration guide: [xrechnung-kit.vineethnk.in/frameworks/wordpress](https://xrechnung-kit.vineethnk.in/frameworks/wordpress).

## License

MIT. See [LICENSE](https://github.com/vineethkrishnan/xrechnung-kit/blob/main/LICENSE).
