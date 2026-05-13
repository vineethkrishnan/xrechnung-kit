# Getting started

This guide takes you from `composer require` to a KoSIT-strict valid XRechnung 3.0 XML on disk in five minutes.

## Install

```bash
composer require vinelabs-de/xrechnung-kit
```

Optional: KoSIT Schematron validation (Java 17+ at validation time):

```bash
composer require --dev vineethkrishnan/xrechnung-kit-kosit-bundle
```

## Requirements

- PHP 8.1, 8.2, 8.3, or 8.4
- `ext-dom`, `ext-libxml`, `ext-mbstring`

Optional:

- `psr/log` to wire a PSR-3 logger
- Java 17+ for KoSIT Schematron validation (only when running `composer kosit` or the CLI)

## Hello, XRechnung

```php
<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;
use XrechnungKit\Mapping\MappingData;

$mappingData = MyInvoiceMapper::fromMyDomainModel($invoice)->produce();

$entity    = XRechnungBuilder::buildEntity($mappingData);
$generator = new XRechnungGenerator($entity);
$path      = $generator->generateXRechnung(__DIR__ . '/Invoice_RE-1.xml');

$validator = new XRechnungValidator();
$ok        = $validator->validate($path);

echo $ok ? "wrote $path\n" : "wrote $path (invalid; quarantined as *_invalid.xml)\n";
```

See [docs/mapping-data.md](mapping-data.md) for what `MappingData` looks like and how to build a mapper for your own domain.

## What just happened

1. `MappingData` was constructed and validated structurally at construction time.
2. `XRechnungBuilder` produced an `XRechnungEntity` with totals, tax breakdown, and document-class selection.
3. `XRechnungGenerator` rendered the entity into a DOMDocument via the bundled UBL template.
4. UBL XSD validation ran in memory against the bundled UBL Invoice 2.4 / CreditNote 2.4 schemas.
5. The XML was written atomically to disk: `Invoice_RE-1.xml` if valid, `Invoice_RE-1_invalid.xml` if invalid. The opposite-sibling file (if any) is removed.
6. The validator surfaced any errors via `XRechnungValidator::getErrors()`.

No network call was made. No telemetry. No global state mutated.

## Adding KoSIT Schematron

UBL XSD validation is a structural floor. KoSIT scenarios add the German federal business rules (BR-DE-* and CIUS rules). To run them:

```bash
composer kosit
```

See [docs/kosit-validation.md](kosit-validation.md) for local, Docker, and CI configurations.

## Next steps

- [Walkthrough](walkthrough/) - six-step tour with rendered terminal and code captures
- [Mapping data contract](mapping-data.md) - the canonical public API contract
- [API overview](reference/api.md) and [generated API reference](/api/) - curated narrative plus the per-class phpDocumentor pages
- [KoSIT Schematron validation](kosit-validation.md) - going beyond UBL XSD
- Framework adapters: [Laravel](frameworks/laravel.md), [Symfony](frameworks/symfony.md), [CakePHP](frameworks/cakephp.md), [Laminas](frameworks/laminas.md)
- [Glossary of German terms](glossary-de.md) - Leitweg-ID, Anzahlung, Rechnungsempfaenger, ...
- [Migrating from easybill/xrechnung-php](migrating-from-easybill.md)
- [Policies](policies.md) - SemVer, deprecation, KoSIT scenarios pinning
