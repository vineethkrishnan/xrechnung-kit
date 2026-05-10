# Using xrechnung-kit with Symfony

The Symfony adapter is a bundle that wires the framework-agnostic core into Symfony's DI container, configuration, messenger, and notifier.

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-symfony
```

For Symfony Flex projects, the recipe enables the bundle automatically. Otherwise, register it manually in `config/bundles.php`:

```php
return [
    // ...
    XrechnungKit\Symfony\XrechnungKitBundle::class => ['all' => true],
];
```

## Configuration

`config/packages/xrechnung_kit.yaml`:

```yaml
xrechnung_kit:
    output_path: '%kernel.project_dir%/var/xrechnung'

    logger:  '@logger'                       # any PSR-3 logger service
    alerter: '@app.xrechnung.alerter'        # any AlerterInterface implementation, null disables

    kosit:
        enabled:   '%env(bool:XRECHNUNG_KIT_KOSIT_ENABLED)%'
        cache_dir: '%env(default:%kernel.cache_dir%/xrechnung:XRECHNUNG_KIT_CACHE_DIR)%'
```

## Generate an XRechnung

```php
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\XRechnungGenerator;

final class InvoiceController
{
    public function __construct(
        private readonly XRechnungGenerator $generator,
    ) {}

    public function __invoke(string $invoiceId): Response
    {
        $mapping = $this->mapperFor($invoiceId)->produce();
        $entity  = XRechnungBuilder::buildEntity($mapping);

        $path = $this->generator->generateXRechnung(
            sprintf('%s/var/xrechnung/RE-%s.xml', $this->projectDir, $invoiceId),
        );

        return new Response($path);
    }
}
```

The bundle autowires `XRechnungBuilder`, `XRechnungGenerator`, and `XRechnungValidator` as services.

## Messenger handler

For background generation, dispatch the bundled message:

```php
use XrechnungKit\Symfony\Message\GenerateXRechnungMessage;
use Symfony\Component\Messenger\MessageBusInterface;

$bus->dispatch(new GenerateXRechnungMessage(
    mapperServiceId: 'app.invoice.mapper',
    targetPath:      $this->pathFor($invoiceId),
));
```

The handler resolves the mapper from the container (by service id) so the message payload stays small.

## Logger binding

Symfony's `monolog` channel is PSR-3 by default. The bundle injects whatever service id you point `logger` at.

## Alerter binding

Implement `XrechnungKit\Alerter\AlerterInterface`. The bundle ships a `NotifierAlerter` that wraps Symfony's notifier; route it to the channels you want via the standard notifier configuration.

## KoSIT validation

```bash
bin/console xrechnung-kit:kosit var/xrechnung
```

The console command is registered by the bundle. Requires the `vineethkrishnan/xrechnung-kit-kosit-bundle` package and Java 17+.

## Testing

The bundle ships a minimal `KernelTestCase`-compatible test app under `adapters/symfony/tests/Application`. Use it as a reference for booting the bundle in your own functional tests.
