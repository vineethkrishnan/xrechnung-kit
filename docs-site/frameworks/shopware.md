# Using xrechnung-kit with Shopware 6

The Shopware 6 plugin wraps the framework-agnostic core as a typed service inside Shopware's Symfony container. Inject it into controllers, subscribers, and order workflow event handlers.

::: tip Status
Scaffold. The package is installable, the public surface is settled, and the integration code (order-to-MappingData mapper, order-state subscriber, admin module) will land as real consumers drive content into it. If you have a near-term Shopware 6 use case for XRechnung, [open an issue](https://github.com/vinelabs-de/xrechnung-kit/issues) describing the integration shape you need.
:::

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-shopware
bin/console plugin:refresh
bin/console plugin:install --activate XrechnungKitShopware
```

Compatible with Shopware 6.5 and 6.6.

## Generate an XRechnung from an order

```php
namespace MyVendor\MyShopwarePlugin\Subscriber;

use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vineethkrishnan\XrechnungKitShopware\Service\XrechnungService;

final class OrderConfirmedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly XrechnungService $xrechnung,
        private readonly OrderToMappingData $mapper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return ['state_machine.order.state_changed' => 'onOrderStateChanged'];
    }

    public function onOrderStateChanged(StateMachineStateChangeEvent $event): void
    {
        if ($event->getTransitionSide() !== 'enter' || $event->getStateName() !== 'completed') {
            return;
        }

        $order = $this->loadOrder($event->getEntityId());
        $mapping = $this->mapper->fromOrder($order);
        $target = sprintf(
            '%s/files/xrechnung/%s.xml',
            $this->projectDir,
            $order->getOrderNumber(),
        );

        $this->xrechnung->generateAndValidate($mapping, $target);
    }
}
```

`OrderToMappingData` is a project-local mapper you write against your shop's customer / billing-address conventions. Read [Mapping data contract](/mapping-data) for the public input shape.

## Where the file goes

The plugin does not pick a storage location. Pass the absolute path you want; the generator writes there atomically. Common targets:

- `var/files/xrechnung/<orderNumber>.xml` for project-local storage
- A media folder served via a Shopware admin route for download

## Public-administration buyers

For B2G orders, the buyer's `BuyerReference` field carries the Leitweg-ID. Make sure your checkout flow captures it as a custom order field; the mapper reads it from there into `Party::publicAdministration(leitwegId: ...)`.

See [Glossary (DE)](/glossary-de) for what Leitweg-ID is and which document type codes apply.

## KoSIT validation

When `vineethkrishnan/xrechnung-kit-kosit-bundle` is installed and Java 17+ is available on the host, the validator exposes a Schematron pass. Run it after generation for full XRechnung compliance:

```php
$valid = $validator->validateSchematron($path);
```

A console command and an admin module that exposes "validate now" with Schematron will land once the scaffold has real content.

## Logging

The plugin binds Shopware's `logger` service (PSR-3 compatible) into the service via DI. Alerts on quarantined invalid files appear in `var/log/xrechnung-kit.log`.

## Testing

For integration tests, use Shopware's `IntegrationTestBehaviour` traits and resolve the service from the test container.

## See also

- [Mapping data contract](/mapping-data)
- [Document type codes](/reference/document-types)
- [API overview](/reference/api)
- [Generated API reference](/api/)
- [KoSIT Schematron validation](/kosit-validation)
