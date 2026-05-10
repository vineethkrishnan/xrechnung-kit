# xrechnung-kit

> EN 16931 / XRechnung 3.0 compliant e-invoice generator and validator for PHP. Framework-agnostic core with first-class adapters for Laravel, Symfony, CakePHP, and Laminas.

[![Packagist](https://img.shields.io/packagist/v/vineethkrishnan/xrechnung-kit-core.svg)](https://packagist.org/packages/vineethkrishnan/xrechnung-kit-core)
[![Tests](https://github.com/vineethkrishnan/xrechnung-kit/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/vineethkrishnan/xrechnung-kit/actions/workflows/test.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892bf.svg)](https://www.php.net/supported-versions)

## What it does

Turns a typed PHP value object describing an invoice into a KoSIT-strict valid XRechnung 3.0 / EN 16931 XML document. Validates the output in memory before writing to disk. Quarantines invalid output. Stays out of your way.

Document classes supported:

- Standard invoice (UNTDID 380)
- Partial invoice / deposit / Anzahlung (UNTDID 326)
- Caution / security deposit
- Credit note / cancellation (UNTDID 381)
- Deposit cancellation

## Installation

```bash
composer require vineethkrishnan/xrechnung-kit-core
```

Optional KoSIT Schematron validation:

```bash
composer require --dev vineethkrishnan/xrechnung-kit-kosit-bundle
```

Framework adapters:

```bash
composer require vineethkrishnan/xrechnung-kit-laravel
composer require vineethkrishnan/xrechnung-kit-symfony
composer require vineethkrishnan/xrechnung-kit-cakephp
composer require vineethkrishnan/xrechnung-kit-laminas
```

Platform integrations (CMS and e-commerce):

```bash
composer require vineethkrishnan/xrechnung-kit-typo3
composer require vineethkrishnan/xrechnung-kit-shopware
composer require vineethkrishnan/xrechnung-kit-wordpress
composer require vineethkrishnan/xrechnung-kit-contenido
```

## Quick start

```php
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\Generator\XRechnungGenerator;
use XrechnungKit\Validator\XRechnungValidator;

$mappingData = (new MyInvoiceMapper($invoice, $customer))->produce();

$entity = XRechnungBuilder::buildEntity($mappingData);
$generator = new XRechnungGenerator($entity);
$path = $generator->generateXRechnung('/path/to/Invoice_RE-1.xml');

$validator = new XRechnungValidator();
$ok = $validator->validate($path);
```

The generator runs UBL XSD validation in memory before writing. Invalid output lands at `*_invalid.xml` and triggers a deduplicated operator alert.

## Why another XRechnung library?

The PHP ecosystem already ships:

- `horstoeko/zugferd` for ZUGFeRD / Factur-X (hybrid PDF), excellent at what it does but not XRechnung 3.0.
- `easybill/xrechnung-php` which generates XML but is one-shot and tightly bound to Easybill's data shapes.
- A handful of smaller libraries that are abandoned, framework-specific, or stop at XSD validation without KoSIT Schematron coverage.

xrechnung-kit aims to fill the remaining gap: KoSIT-strict valid XRechnung 3.0 from a clean framework-agnostic core, with documented adapter packages for the four major PHP frameworks.

## Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- `ext-libxml`, `ext-dom`, `ext-mbstring`
- Optional `psr/log`
- Java 11+ for KoSIT Schematron validation (only when running `validate-kosit`)

## Documentation

Full documentation is hosted at **[xrechnung-kit.vineethnk.in](https://xrechnung-kit.vineethnk.in/)** (also reachable at [xrechnung-kit.pages.dev](https://xrechnung-kit.pages.dev/)). Source lives under `docs-site/` and auto-deploys to Cloudflare Pages on every push to `main`.

- [Getting started](https://xrechnung-kit.vineethnk.in/getting-started)
- [Walkthrough](https://xrechnung-kit.vineethnk.in/walkthrough/) (step-by-step from `composer require` to a valid XML)
- [MappingData: the canonical contract](https://xrechnung-kit.vineethnk.in/mapping-data)
- [KoSIT Schematron validation](https://xrechnung-kit.vineethnk.in/kosit-validation)
- Framework adapters: [Laravel](https://xrechnung-kit.vineethnk.in/frameworks/laravel), [Symfony](https://xrechnung-kit.vineethnk.in/frameworks/symfony), [CakePHP](https://xrechnung-kit.vineethnk.in/frameworks/cakephp), [Laminas](https://xrechnung-kit.vineethnk.in/frameworks/laminas)
- Platform integrations: [TYPO3](https://xrechnung-kit.vineethnk.in/frameworks/typo3), [Shopware 6](https://xrechnung-kit.vineethnk.in/frameworks/shopware), [WordPress / WooCommerce](https://xrechnung-kit.vineethnk.in/frameworks/wordpress), [Contenido CMS](https://xrechnung-kit.vineethnk.in/frameworks/contenido)
- [Extending xrechnung-kit](https://xrechnung-kit.vineethnk.in/extending) - custom mappers, channels, loggers, validators
- [API overview](https://xrechnung-kit.vineethnk.in/reference/api) and [generated API reference](https://xrechnung-kit.vineethnk.in/api/)
- [Document type codes](https://xrechnung-kit.vineethnk.in/reference/document-types)
- [Glossary of German XRechnung terms](https://xrechnung-kit.vineethnk.in/glossary-de)
- [Versioning and compatibility policies](https://xrechnung-kit.vineethnk.in/policies)
- [Upgrading from 0.x to 1.0](https://xrechnung-kit.vineethnk.in/upgrading/0.x-to-1.0)
- [Migrating from easybill/xrechnung-php](https://xrechnung-kit.vineethnk.in/migrating-from-easybill)

## Versioning

Semantic versioning. The project's compatibility promises:

- XML output is byte-stable within a patch release.
- KoSIT scenarios may bump within a minor as long as pass/fail behaviour is preserved.
- Anything that changes emitted XML or the public API ships in a major.

## Privacy

No telemetry. No analytics. No runtime network calls during XML generation. KoSIT validation requires the bundle to be installed locally; the bundle itself is fetched only via Composer.

## Security

Disclosure via GitHub private vulnerability reporting on this repository, or by email to `me@vineethnk.in`. Embargo policy and response timeline in [SECURITY.md](SECURITY.md).

## Repository layout

This monorepo holds the framework-agnostic core under `core/`, the optional KoSIT bundle under `kosit-bundle/`, framework adapters under `adapters/`, and shared mappers under `mappers/`. Each subtree carries its own `composer.json` so it can be published as a standalone Packagist package.

`core/` is auto-mirrored to [`vineethkrishnan/xrechnung-kit-core`](https://github.com/vineethkrishnan/xrechnung-kit-core) on every push to `main` and on every `v*.*.*` tag, via `splitsh-lite` and the `.github/workflows/split-and-publish.yml` workflow. Packagist resolves `vineethkrishnan/xrechnung-kit-core` against that mirror. Do not open PRs against the mirror; all development happens here.

## Contributing

Bug reports, feature requests, and PRs are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a PR. Code of conduct in [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

[MIT](LICENSE). Bundled UBL XSDs and KoSIT scenarios retain their original licenses; see [LICENSE-third-party.md](LICENSE-third-party.md).

## Trademark notice

"XRechnung" is a German federal e-invoicing standard maintained by KoSIT (Koordinierungsstelle fuer IT-Standards). xrechnung-kit is an independent open source library and is neither affiliated with nor endorsed by KoSIT or any German government agency.
