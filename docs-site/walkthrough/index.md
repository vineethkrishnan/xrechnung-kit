---
title: Walkthrough
description: Six-step tour from `composer require` to a KoSIT-strict valid XRechnung 3.0 XML on disk, with the queueable Laravel variant on the side.
---

# Walkthrough

A focused, six-step tour through the full pipeline. Each step shows the code or output exactly as you would see it on a fresh project. The screenshots are generated from real, runnable inputs under `tools/screenshots/snippets/` and regenerate via `npm run screenshots:cli`.

If you have already installed the package and want to see the equivalent terminal session, run the bundled standalone example:

```bash
php examples/standalone/generate.php
```

It writes a sample invoice to `examples/standalone/out/Demo-Invoice-001.xml` and reports validator pass/fail. The same pipeline is what the framework adapters wrap.

---

## Step 1: Install

```bash
composer require vineethkrishnan/xrechnung-kit-core
```

Optional bundles (only when the corresponding feature is needed):

```bash
# KoSIT Schematron validation. Adds the official validator JAR plus
# scenarios. Requires Java 17+ at validation time.
composer require --dev vineethkrishnan/xrechnung-kit-kosit-bundle

# Framework adapter for Laravel.
composer require vineethkrishnan/xrechnung-kit-laravel
```

`xrechnung-kit-core` has zero hard runtime dependencies beyond `ext-dom`, `ext-libxml`, and `ext-mbstring`. PSR-3 logging is opt-in.

## Step 2: Build a typed `MappingData`

The entire public input contract is a single value object: `XrechnungKit\Mapping\MappingData`. Five named constructors map to the five supported document classes. Each constructor is total: required fields are constructor parameters, optional fields are nullable.

![PHP code: building MappingData with named constructors](/walkthrough/01-mapping-data.png)

The value objects validate structurally at construction time. If you forget the seller's tax-id or pass a non-EUR currency to a `Money::eur` factory, you get a `\InvalidArgumentException` at the point of construction, not deep inside XML generation.

See [Mapping data contract](/mapping-data) for the full surface.

## Step 3: Generate

The generator is a thin orchestrator over the lifted L3 pipeline: `XRechnungBuilder` produces a typed `XRechnungEntity`, `XRechnungGenerator` substitutes it into the bundled UBL template, runs UBL XSD validation in memory, and writes atomically to the target path.

```bash
php examples/standalone/generate.php
```

![Terminal output: generated path, invoice summary, validator PASS](/walkthrough/02-run-generate.png)

The output file is exactly the path you asked for. No suffix games on the success path.

## Step 4: Inspect the output

The generated file is XRechnung 3.0 / EN 16931 conformant UBL Invoice. The first lines carry the standard envelope and identifiers KoSIT looks for:

![XML excerpt: ubl:Invoice envelope, CustomizationID, ProfileID, ID, dates, parties](/walkthrough/03-xml-output.png)

The `CustomizationID` `urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0` is the marker every KoSIT validator looks for. The `ProfileID` is set to the PEPPOL BIS Billing 3.0 profile. Output is byte-stable within a patch release.

## Step 5: Validate

UBL XSD validation has already happened in memory before the file landed on disk. The standalone validator gives you a defensive second pass, plus an optional KoSIT Schematron pass when the bundle is installed.

![PHP: XRechnungValidator usage with XSD and Schematron passes](/walkthrough/04-validator.png)

If XSD validation fails at write time, the file is written to a `*_invalid.xml` sibling instead of the requested path. The opposite-sibling file (if any) is removed atomically, so you cannot end up with both a valid and an invalid version of the same invoice number.

![Terminal output: quarantined invalid file, error breakdown, ls of out/](/walkthrough/05-quarantine.png)

A deduplicated alert is emitted through your configured PSR-3 logger. Repeat invalid generations of the same invoice within a configurable window collapse into a single alert so you do not flood ops on a stuck consumer.

## Step 6: Wire it into your stack

In a PSR-only stack, hand-build the validator and generator as shown above. In a framework, install the matching adapter and let it register everything in the container, log stack, and queue.

![PHP: Laravel queueable job dispatching XRechnung generation](/walkthrough/06-laravel-job.png)

Adapter setup details for each framework:

- [Laravel](/frameworks/laravel) - service provider, facade, queueable job
- [Symfony](/frameworks/symfony) - bundle and DI extension
- [CakePHP](/frameworks/cakephp) - plugin and service
- [Laminas](/frameworks/laminas) - module and service factory

## What you have at the end

After running the standalone example or the equivalent flow in your own app, you have:

- A KoSIT-strict valid XRechnung 3.0 XML at the path you asked for.
- A guarantee that no invalid XML was ever written to that path. If validation failed, the path holds nothing and the broken bytes are at `*_invalid.xml` for inspection.
- A PSR-3 alert at most once per invoice number per cool-down window when invalid output is generated.
- Zero network calls. Zero telemetry. Zero global state mutated.

Continue with [Mapping data contract](/mapping-data) for the full input shape, [API overview](/reference/api) for the curated public surface (or the per-class [generated reference](/api/)), or [KoSIT Schematron validation](/kosit-validation) for the optional federal-business-rule pass.
