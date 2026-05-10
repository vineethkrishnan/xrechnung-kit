# PDF/A-3 hybrid invoices

The `vineethkrishnan/xrechnung-kit-pdfa` package embeds a generated XRechnung 3.0 XML inside a PDF/A-3 visual representation. The result is a single hybrid file usable both by humans (any PDF reader) and by KoSIT-strict validators (the embedded XML is the canonical truth, the PDF is for human consumption).

::: tip Status
The contract and value objects are settled and tested. The reference producer (mpdf-based) lands in a follow-up. For now, bind your own `Pdfa3EmbedderInterface` implementation - the surface is small.
:::

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-pdfa
```

The package itself has zero PDF-library dependencies. Pick the producer that fits your stack:

```bash
# recommended for most projects
composer require mpdf/mpdf:^8.2

# alternative if your project already produces PDFs via fpdi
composer require setasign/fpdi:^2.5
```

## Public surface

| Type | Purpose |
| ---- | ------- |
| `XrechnungKit\Pdfa\Pdfa3EmbedderInterface` | The contract: `name()`, `isAvailable()`, `embed()`, `renderAndEmbed()`. |
| `XrechnungKit\Pdfa\EmbeddingOptions` | Typed options: output path, attachment name (default `xrechnung.xml`), AFRelationship (`Source` / `Data` / `Alternative`), conformance level (`A` / `B`, default `B`), XMP metadata fields, overwrite flag. Constructor validates the enum-like fields. |
| `XrechnungKit\Pdfa\EmbeddingResult` | Typed outcome: output path, size in bytes, whether XMP metadata was applied, structured warnings list. |
| `XrechnungKit\Pdfa\Stub\StubPdfa3Embedder` | Placeholder that throws with a guidance pointer; useful in tests, **not** in production. |

## Wiring

```php
use XrechnungKit\Pdfa\EmbeddingOptions;
use XrechnungKit\Pdfa\Pdfa3EmbedderInterface;

final class HybridInvoiceProducer
{
    public function __construct(
        private readonly Pdfa3EmbedderInterface $embedder,
    ) {}

    public function produce(string $invoiceXmlPath, string $basePdfPath, string $targetPath): void
    {
        $result = $this->embedder->embed(
            basePdfPath: $basePdfPath,
            xrechnungXmlPath: $invoiceXmlPath,
            options: new EmbeddingOptions(
                outputPath: $targetPath,
                title: 'Invoice',
                producer: 'My Project Invoicing v3',
                conformanceLevel: EmbeddingOptions::CONFORMANCE_LEVEL_B,
            ),
        );

        if ($result->hasWarnings()) {
            // structured downgrade or non-fatal observations
        }
    }
}
```

## Why PDF/A-3, not just PDF

PDF/A is the long-term-archival profile of PDF. PDF/A-3 specifically allows arbitrary file attachments (PDF/A-1 and A-2 do not), which is what makes hybrid invoices possible: the visual PDF that humans read on one side, the machine-readable XRechnung XML attached on the other.

The XMP metadata signals to ZUGFeRD-aware consumers that the attached XML is the canonical invoice data, with `AFRelationship` of `Source`. xrechnung-kit-pdfa applies the same XMP convention so consumers that already understand ZUGFeRD pick up XRechnung hybrids without changes.

## Why a separate package

PDF library choice is opinionated and rotates over time (mpdf, fpdi, tcpdf, project-internal renderers, headless Chromium). Keeping the contract in its own package lets the rest of xrechnung-kit stay PDF-agnostic, and lets PDF library upgrades happen independently of the core.

## See also

- [Mapping data contract](/mapping-data) - how the underlying XML is built
- [API overview](/reference/api)
- [Generated API reference](/api/)
- [Versioning policies](/policies)
