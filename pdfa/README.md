# xrechnung-kit-pdfa

PDF/A-3 hybrid invoice support for [xrechnung-kit](https://github.com/vineethkrishnan/xrechnung-kit). Embeds an XRechnung 3.0 XML inside a PDF/A-3 visual representation - the result is a single file usable both by humans (open in any PDF reader) and by KoSIT-strict validators (the embedded XML is the canonical truth).

This is the source under `pdfa/` of the monorepo, published to Packagist as a standalone package via the auto-publish workflow.

## Why a separate package

PDF/A-3 production is opinionated and the right PDF library choice rotates over time (mpdf, fpdi, tcpdf, project-internal renderers). The kit ships only the contract plus a stub; consumers bind whichever producer fits their stack.

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-pdfa
```

Then bind a real producer in your application's container, e.g. with mpdf:

```php
$embedder = new \YourApp\Pdfa\MpdfPdfa3Embedder($mpdfFactory);

$result = $embedder->embed(
    basePdfPath: '/var/files/invoice-001.pdf',
    xrechnungXmlPath: '/var/files/xrechnung/Invoice-001.xml',
    options: new \XrechnungKit\Pdfa\EmbeddingOptions(
        outputPath: '/var/files/invoice-001.hybrid.pdf',
        title: 'Invoice 001',
    ),
);

if ($result->hasWarnings()) {
    foreach ($result->warnings as $warning) {
        $logger->info('xrechnung-kit-pdfa: ' . $warning);
    }
}
```

## Public surface

- `XrechnungKit\Pdfa\Pdfa3EmbedderInterface` - the contract
- `XrechnungKit\Pdfa\EmbeddingOptions` - typed options (output path, attachment name, AFRelationship, conformance level, XMP metadata fields)
- `XrechnungKit\Pdfa\EmbeddingResult` - typed outcome (output path, size, XMP applied flag, warnings)
- `XrechnungKit\Pdfa\Stub\StubPdfa3Embedder` - placeholder that throws with a guidance pointer; replace in production

## Status

Pre-alpha scaffold. The interfaces and value objects are settled and tested. The reference mpdf-based producer lands in a follow-up that pins a specific mpdf version line and walks the PDF/A-3 conformance steps explicitly. Until then, bring your own producer.

## License

MIT. See [LICENSE](https://github.com/vineethkrishnan/xrechnung-kit/blob/main/LICENSE).
