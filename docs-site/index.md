---
layout: home
title: xrechnung-kit
titleTemplate: KoSIT-strict valid XRechnung 3.0 for PHP

hero:
  name: xrechnung-kit
  text: EN 16931 / XRechnung 3.0 for PHP
  tagline: A KoSIT-strict valid e-invoice generator and validator. A framework-agnostic core, with first-class adapters for Laravel, Symfony, CakePHP, and Laminas.
  image:
    src: /logo.svg
    alt: xrechnung-kit monogram
  actions:
    - theme: brand
      text: Get started
      link: /getting-started
    - theme: alt
      text: Walkthrough
      link: /walkthrough/
    - theme: alt
      text: View on GitHub
      link: https://github.com/vinelabs-de/xrechnung-kit

features:
  - title: KoSIT-strict valid output
    details: Generates XRechnung 3.0 XML that satisfies UBL XSD and the optional KoSIT Schematron rules (BR-DE-* and CIUS). Output is byte-stable within a patch release.
  - title: Validate before write
    details: UBL XSD validation runs in memory before the file ever lands on disk. Invalid output is quarantined as `*_invalid.xml` and a deduplicated alert is raised through your configured PSR-3 logger.
  - title: Typed contract
    details: A single `MappingData` value object is the entire public input contract. Five named constructors cover the supported document classes. Bring your own mapper from your domain model.
  - title: No magic, no telemetry
    details: No runtime network calls during XML generation. No analytics. No auto-discovery side effects. Everything that touches disk does so through an atomic temp+rename writer.
  - title: First-class framework adapters
    details: Laravel, Symfony, CakePHP, and Laminas adapter packages register the core into the container, the queue, and the log stack idiomatically. Use the core directly in PSR-only stacks.
  - title: PHP 8.1 to 8.4
    details: Tested against PHP 8.1, 8.2, 8.3, and 8.4 across PHPUnit 10, 11, and 12. Continuously validated against UBL Invoice 2.4 and CreditNote 2.4 schemas.

---

## Document classes supported

| Code | Name | Constructor |
| ---- | ---- | ----------- |
| 380  | Standard invoice | `MappingData::standardInvoice(...)` |
| 326  | Partial invoice / Anzahlung | `MappingData::partialInvoice(...)` |
| 326  | Caution / security deposit | `MappingData::cautionInvoice(...)` |
| 381  | Credit note / cancellation | `MappingData::creditNote(...)` |
| 381  | Deposit cancellation | `MappingData::depositCancellation(...)` |

## At a glance

```bash
composer require vinelabs-de/xrechnung-kit
```

```php
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

$entity    = XRechnungBuilder::buildEntity($mappingData);
$generator = new XRechnungGenerator($entity);
$path      = $generator->generateXRechnung('out/Invoice-001.xml');

$validator = new XRechnungValidator();
$ok = $validator->validate($path);
```

The generator runs UBL XSD validation in memory before writing. If validation fails, the file is written to a `*_invalid.xml` sibling for inspection rather than the requested path; a deduplicated operator alert is emitted through PSR-3.

See the full [walkthrough](/walkthrough/) for a step-by-step tour from `composer require` to a Schematron-valid file on disk. The complete public surface is in the [API overview](/reference/api), with per-class detail in the [generated reference](/api/).

## Status and compliance

xrechnung-kit is published on Packagist as [`vinelabs-de/xrechnung-kit`](https://packagist.org/packages/vinelabs-de/xrechnung-kit). It targets the [XRechnung 3.0](https://xeinkauf.de/xrechnung/) standard and the underlying [EN 16931](https://standards.cen.eu/dyn/www/f?p=204:110:0::::FSP_PROJECT,FSP_LANG_ID:60602,25&cs=1B61B766636F9FB34B7DBD72CF9357F75) European norm. The bundled schemas are the official [OASIS UBL 2.4](http://docs.oasis-open.org/ubl/UBL-2.4.html) Invoice and CreditNote XSDs. Schematron validation is performed by the [official KoSIT validator](https://github.com/itplr-kosit/validator), wrapped by the optional `kosit-bundle` package and gated behind a Java 17+ requirement at validation time only.

xrechnung-kit is an independent open source library, distributed under the MIT license. It is neither affiliated with nor endorsed by KoSIT, the Bundesregierung, or any German federal or state agency. The "XRechnung" name refers to the federal e-invoicing standard maintained by KoSIT (Koordinierungsstelle fuer IT-Standards). See [trademarks](/policies#trademarks) for full wording.
