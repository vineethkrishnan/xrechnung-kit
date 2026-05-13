# Migrating from easybill/xrechnung-php

`easybill/xrechnung-php` is the closest existing PHP library to xrechnung-kit. If you are already using it and considering a switch, this page covers what is different and how to move over without breaking your generated output.

## At a glance

| Topic | easybill/xrechnung-php | xrechnung-kit |
|---|---|---|
| Public input shape | Easybill domain classes | Typed `MappingData` value-object graph |
| Document types | Invoice, Credit Note | Invoice (380), Partial / Anzahlung (326), Caution, Credit Note (381), Deposit cancellation |
| Validation | XSD | UBL XSD in-memory + optional KoSIT Schematron via separate bundle |
| Atomic write to disk | No (string return) | Yes (tempfile + rename, with `*_invalid.xml` quarantine) |
| Operator alerting on invalid output | No | `AlerterInterface` with built-in dedup |
| Framework adapters | None | Laravel, Symfony, CakePHP, Laminas |
| KoSIT scenarios bundled | No (consumer downloads) | Optional separate package, pinned scenarios |
| Determinism (byte-stable XML) | Best-effort | Contractual within a patch release |
| Telemetry / runtime network | None | None |
| Output XML scope | XRechnung 2.x and earlier | XRechnung 3.0 / EN 16931 |
| Branch-alias / SemVer on XML | Implicit | Documented matrix |

## What stays the same

- The output is XRechnung XML against the same UBL Invoice 2.x / CreditNote 2.x envelopes.
- KoSIT-strict validation on top is still on you to run; xrechnung-kit makes it one composer command instead of a manual setup.

## Migration recipe

### Step 1: install side-by-side

You can run both libraries in the same project during the transition. Different namespaces, different vendor.

```bash
composer require vinelabs-de/xrechnung-kit
```

### Step 2: write a mapper from your existing Easybill input

Wherever you currently construct an Easybill invoice object, write a function that produces a `MappingData` from the same source data. The mapper is the only domain-specific code you need.

```php
final class FromEasybillMapper implements SourceMapperInterface
{
    public function __construct(private readonly EasybillInvoice $easybillInvoice) {}

    public function produce(): MappingData
    {
        return MappingData::standardInvoice(
            meta:    $this->buildMeta(),
            seller:  $this->buildSeller(),
            buyer:   $this->buildBuyer(),
            lines:   $this->buildLines(),
            payment: $this->buildPayment(),
        );
    }
}
```

### Step 3: feed both pipelines and diff the XML

For a transition period, generate XML with both libraries and diff the canonical-form output. Differences worth investigating:

- Tax breakdown ordering (xrechnung-kit sorts by `(category, percent)` for determinism)
- BG-14 emission on partial / caution / deposit cancellation (xrechnung-kit always emits; some easybill setups do not)
- `cbc:DueDate` (BT-9) emission
- `BillingReference` on credit notes (BR-DE-22)

### Step 4: validate both with KoSIT

```bash
composer kosit
```

Differences that pass KoSIT either way are stylistic; differences where one passes and the other fails are real and should be filed as a bug if xrechnung-kit is the one failing.

### Step 5: cut over and remove the old library

Once your fixture diff is empty (or only stylistic), remove `easybill/xrechnung-php` and the bridging mapper.

## Things you cannot do in xrechnung-kit (yet)

- Hybrid PDF / Factur-X / ZUGFeRD output. Use [`horstoeko/zugferd`](https://github.com/horstoeko/zugferd) for that.
- PEPPOL BIS Billing 3.0 (the international CIUS, not the German one). Tracked in the architecture roadmap as "profile resolver".
- Custom UBL extensions via `<ext:UBLExtensions>`. Out of scope; xrechnung-kit emits the XRechnung 3.0 subset only.

## Things easybill does that xrechnung-kit deliberately does not

- Accept associative-array input. xrechnung-kit takes typed value objects so the IDE catches mistakes.
- Return XML strings. xrechnung-kit writes to disk atomically; if you need a string, read the file (or call the in-memory generator and grab the DOMDocument before write).
- Silently transliterate input characters. xrechnung-kit surfaces character issues at `MappingData` construction.

## Help

If you hit a migration blocker, open a [GitHub Discussion](https://github.com/vinelabs-de/xrechnung-kit/discussions) with a small reproduction (Easybill input + intended XRechnung output).
