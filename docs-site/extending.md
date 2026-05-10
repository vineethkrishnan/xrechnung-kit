---
title: Extending xrechnung-kit
description: Public extension points for developers integrating xrechnung-kit into their own application or extending features later. Covers custom mappers, custom notification channels, custom loggers, custom validation passes, and project-local builder overrides.
---

# Extending xrechnung-kit

This page is for developers who plan to integrate xrechnung-kit into their own application and want to extend or customise features later. It documents the **public extension points** that are part of the SemVer contract: anything below is safe to depend on across minor releases, and any breaking change here ships in a major.

The kit is intentionally small. Most extension is by **composition**, not inheritance.

## Extension surface at a glance

| Goal | Mechanism | Where to start |
| ---- | --------- | -------------- |
| Map your domain to XRechnung | Build a `MappingData` from your models | [Mapping data contract](/mapping-data) |
| Plug in a custom logger | `XrechnungKit\Logger\LoggerInterface` (PSR-3 subset) or pass any PSR-3 logger | [Custom logger](#custom-logger) |
| Add a notification channel (Sentry, Discord, etc.) | `XrechnungKit\Notification\NotificationChannelInterface` | [Custom notification channel](#custom-notification-channel) |
| Control how channels are dispatched (filtering, dedup, fanout) | `XrechnungKit\Notification\NotificationDispatcherInterface` | [Custom dispatcher](#custom-dispatcher) |
| Add project-specific validation rules | Subclass or wrap `XRechnungValidator` | [Custom validation passes](#custom-validation-passes) |
| Replace storage backend (S3, GCS, encrypted volume) | Implement `XrechnungKit\AtomicWriter` interface | [Custom writer](#custom-writer) |
| Add a new document class | Open an issue first - this is core surface, not an extension point | [Why](#new-document-classes) |

The internal namespace (`XrechnungKit\Internal\`) is **not** extensible and may change in any minor release. If you need to extend something there, open an issue describing the use case so we can lift it into the public surface intentionally.

## Custom mapper

The canonical extension point. xrechnung-kit does not know about your domain - you do. Build a `MappingData` from your invoice and customer models, hand it off to the pipeline.

```php
use XrechnungKit\Mapping\{
    MappingData, DocumentMeta, Party, Address, LineItem, Money,
    TaxBreakdown, PaymentMeans, TaxId, DocumentTotals,
};
use XrechnungKit\XRechnungInvoiceTypeCode;
use XrechnungKit\XRechnungTaxCategory;

final class InvoiceToMappingData
{
    public function __construct(private readonly Invoice $invoice) {}

    public function produce(): MappingData
    {
        return MappingData::standardInvoice(
            meta: new DocumentMeta(
                invoiceNumber: $this->invoice->number,
                type:          XRechnungInvoiceTypeCode::COMMERCIAL_INVOICE,
                issueDate:     $this->invoice->issuedAt,
                currency:      $this->invoice->currency,
            ),
            seller: $this->mapSeller(),
            buyer:  $this->mapBuyer(),
            lines:  array_map([$this, 'mapLine'], $this->invoice->lines),
            taxes:  $this->mapTaxBreakdowns(),
            payment: $this->mapPaymentMeans(),
            totals: $this->mapTotals(),
        );
    }

    // ... mapSeller, mapBuyer, mapLine, etc. ...
}
```

`MappingData` validates structurally at construction time. Currency mismatches, missing required fields, and inconsistent tax breakdowns throw `\InvalidArgumentException` synchronously - bugs in your mapper surface during the test suite, not at generation time in production.

## Custom logger

The core uses a small PSR-3 subset:

```php
namespace XrechnungKit\Logger;

interface LoggerInterface
{
    public function info(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
}
```

Pass any PSR-3 logger directly to `XRechnungValidator::__construct()`. The framework adapter packages do this for you (Laravel binds `Illuminate\Log\Logger`, Symfony binds `monolog`, etc.). For PSR-only stacks:

```php
$validator = new XRechnungValidator($myPsr3Logger);
```

To skip logging entirely, use the bundled `XrechnungKit\Logger\NullLogger`.

## Custom notification channel

Channels are how operator alerts are delivered when an invoice fails validation and lands in quarantine. The kit ships six built-in channels: Slack, webhook, email, log, callable, and null. Add a new one (Sentry, Discord, Microsoft Teams, internal incident system) by implementing one interface:

```php
namespace XrechnungKit\Notification;

interface NotificationChannelInterface
{
    public function send(Notification $notification): void;
    public function name(): string; // stable id: "slack", "email", "sentry", ...
}
```

Concrete example:

```php
use Sentry\State\HubInterface;
use XrechnungKit\Notification\Notification;
use XrechnungKit\Notification\NotificationChannelInterface;
use XrechnungKit\Notification\Severity;

final class SentryChannel implements NotificationChannelInterface
{
    public function __construct(private readonly HubInterface $hub) {}

    public function send(Notification $notification): void
    {
        $this->hub->captureMessage(
            sprintf('%s: %s', $notification->title, $notification->body),
            match ($notification->severity) {
                Severity::Error   => \Sentry\Severity::error(),
                Severity::Warning => \Sentry\Severity::warning(),
                Severity::Info    => \Sentry\Severity::info(),
            },
        );
    }

    public function name(): string
    {
        return 'sentry';
    }
}
```

Implementations should **swallow transient delivery errors**; the pipeline must not crash because Slack is down.

Wire your channel into the dispatcher:

```php
$dispatcher = new ChannelDispatcher([
    new SlackChannel($slackWebhookUrl),
    new SentryChannel($hub),
]);
```

## Custom dispatcher

For more control over fanout, deduplication, and routing, implement the dispatcher contract directly:

```php
namespace XrechnungKit\Notification;

interface NotificationDispatcherInterface
{
    public function dispatch(Notification $notification): void;
}
```

Use cases:

- **Severity routing:** dispatch errors to PagerDuty, warnings to Slack, info to a log channel.
- **Customer-side filtering:** suppress notifications about test fixtures.
- **Custom deduplication:** the bundled `DeduplicatingDispatcher` collapses identical signatures within a window; replace with your own logic.

```php
final class SeverityRoutingDispatcher implements NotificationDispatcherInterface
{
    public function __construct(
        private readonly NotificationDispatcherInterface $errors,
        private readonly NotificationDispatcherInterface $warnings,
    ) {}

    public function dispatch(Notification $notification): void
    {
        $target = $notification->severity === Severity::Error ? $this->errors : $this->warnings;
        $target->dispatch($notification);
    }
}
```

## Custom validation passes

For project-specific business rules that go beyond UBL XSD and KoSIT Schematron - for example, "every B2G invoice must reference a project code matching `^P\d{6}$`" - extend the validator:

```php
use XrechnungKit\XRechnungValidator;

final class ProjectCodeAwareValidator extends XRechnungValidator
{
    public function validate(string $path): bool
    {
        if (!parent::validate($path)) {
            return false;
        }
        return $this->validateProjectCode($path);
    }

    private function validateProjectCode(string $path): bool
    {
        $dom = new \DOMDocument();
        $dom->load($path);
        $code = $dom->getElementsByTagName('ProjectReference')[0]?->nodeValue ?? '';
        if (preg_match('/^P\d{6}$/', $code) !== 1) {
            $this->errors[] = sprintf(
                'Project code %s does not match expected format ^P\\d{6}$',
                $code,
            );
            return false;
        }
        return true;
    }
}
```

Use the same `validate()` / `getErrors()` contract everywhere; framework adapters bind your subclass via container alias instead of the default.

A second pattern, useful when you have several rules: collect rules behind your own `RuleInterface` and run each over the parsed `DOMDocument`. Keep rules side-effect-free so they are unit-testable.

## Custom writer

The default writer (`XrechnungKit\Internal\AtomicWriter`) writes to a local filesystem path with a temp+rename atomic guarantee. To replace - for example, to stream directly to S3, encrypt at rest, or push to a different storage abstraction - inject a custom writer at the point where the Generator's output is consumed.

This is currently consumer-driven: the generator returns the path it would write, and you choose what to do with it. A first-class `WriterInterface` extension point will land in a future minor release if there is demand. Open an issue describing your storage scenario.

## New document classes

The five supported document classes (UNTDID 380, 326x2, 381x2) are part of the core public surface. Adding a new one - say, UNTDID 71 (request for payment) - is **not** an extension point; it changes the public API contract and the bundled UBL templates, which is core work. Open an issue with the German use case, the typical scenario, and the KoSIT validator output you expect.

## Replacing the bundled UBL template

The XML template is bundled with the core and not currently swappable. The intent is that consumers describe their invoice through `MappingData`; the kit owns how that becomes XML. Diverging from the bundled template defeats the byte-stability guarantee. If your German use case needs a template variation, raise it - it is more likely a missing `MappingData` field or a missing document class than a template-replacement need.

## Versioning of the extension surface

Everything documented on this page is part of the public contract. The promises:

- An interface signature does not change in a minor release. New methods come with default implementations or as optional separate interfaces.
- Removing or renaming an interface ships in a major release with a deprecation cycle.
- Internal namespace types (`XrechnungKit\Internal\*`) may change at any time and are intentionally not extensible.

See [Versioning policies](/policies) for the full SemVer contract.

## See also

- [Mapping data contract](/mapping-data) - the canonical extension point
- [API overview](/reference/api) - public surface map
- [Generated API reference](/api/) - per-class detail
- [KoSIT Schematron validation](/kosit-validation) - the optional federal-business-rule pass
