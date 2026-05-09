# xrechnung-kit

> EN 16931 / XRechnung 3.0 compliant e-invoice generator and validator for PHP. Framework-agnostic core with first-class adapters for Laravel, Symfony, CakePHP, and Laminas.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892bf.svg)](https://www.php.net/supported-versions)
[![Status](https://img.shields.io/badge/status-pre--alpha-orange.svg)](#status)

## Status

Pre-alpha. The repository is being scaffolded ahead of an extraction from a working production implementation. No tagged release yet. Watch the repo for the v0.1.0 announcement.

## What it does

Turns a typed PHP value object describing an invoice into a KoSIT-strict valid XRechnung 3.0 / EN 16931 XML document. Validates the output in memory before writing to disk. Quarantines invalid output. Stays out of your way.

Document classes supported at v1.0:

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

Documentation lands under `docs/` as code is published. Initial entries planned:

- `docs/getting-started.md`
- `docs/mapping-data.md` - the canonical contract
- `docs/kosit-validation.md` - local, Docker, and CI usage
- `docs/frameworks/{laravel,symfony,cakephp,laminas}.md`
- `docs/migrating-from-easybill.md`
- `docs/glossary-de.md` - German XRechnung terms mapped to library concepts

## Versioning

Semantic versioning once v1.0.0 ships. Until then expect minor v0.x bumps to be breaking. After v1.0.0:

- XML output is byte-stable within a patch release.
- KoSIT scenarios may bump within a minor as long as pass/fail behaviour is preserved.
- Anything that changes emitted XML or the public API ships in a major.

## Privacy

No telemetry. No analytics. No runtime network calls during XML generation. KoSIT validation requires the bundle to be installed locally; the bundle itself is fetched only via Composer.

## Security

Disclosure via GitHub private vulnerability reporting on this repository, or by email to `me@vineethnk.in`. Embargo policy and response timeline in [SECURITY.md](SECURITY.md).

## Contributing

Bug reports, feature requests, and PRs are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a PR. Code of conduct in [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

[MIT](LICENSE). Bundled UBL XSDs and KoSIT scenarios retain their original licenses; see [LICENSE-third-party.md](LICENSE-third-party.md).

## Trademark notice

"XRechnung" is a German federal e-invoicing standard maintained by KoSIT (Koordinierungsstelle fuer IT-Standards). xrechnung-kit is an independent open source library and is neither affiliated with nor endorsed by KoSIT or any German government agency.
