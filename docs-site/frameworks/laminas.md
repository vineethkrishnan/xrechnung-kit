# Using xrechnung-kit with Laminas

The Laminas adapter is a module that wires the framework-agnostic core into Laminas MVC's service manager and event manager.

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-laminas
```

Register the module in `config/modules.config.php`:

```php
return [
    // ...
    'XrechnungKit\\Laminas',
];
```

## Configuration

`config/autoload/xrechnung-kit.global.php`:

```php
return [
    'xrechnung_kit' => [
        'output_path' => __DIR__ . '/../../data/xrechnung',

        'logger'  => 'XrechnungKit\\Logger',          // any PSR-3 logger service name
        'alerter' => 'XrechnungKit\\Alerter\\Null',   // any AlerterInterface service name

        'kosit' => [
            'enabled'   => (bool) (getenv('XRECHNUNG_KIT_KOSIT_ENABLED') ?: false),
            'cache_dir' => getenv('XRECHNUNG_KIT_CACHE_DIR') ?: __DIR__ . '/../../data/cache/xrechnung-kit',
        ],
    ],
];
```

## Generate an XRechnung

```php
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\XRechnungGenerator;

final class InvoiceController extends AbstractActionController
{
    public function __construct(
        private readonly XRechnungGenerator $generator,
    ) {}

    public function generateAction()
    {
        $mapping = $this->mapperFor($this->params('id'))->produce();
        $entity  = XRechnungBuilder::buildEntity($mapping);
        $path    = $this->generator->generateXRechnung(
            __DIR__ . '/../../../data/xrechnung/RE-' . $this->params('id') . '.xml'
        );

        return new JsonModel(['path' => $path]);
    }
}
```

The module's `ConfigProvider` registers `XRechnungBuilder`, `XRechnungGenerator`, and `XRechnungValidator` factories so the service manager can construct them on demand.

## Logger binding

Provide any service that implements PSR-3 `Psr\Log\LoggerInterface`. The module's wiring resolves the configured service name via the service manager.

## Alerter binding

Implement `XrechnungKit\Alerter\AlerterInterface` and register it in the service manager under the configured name. The module ships a `NullAlerter` and a Mezzio-friendly `EventManagerAlerter` that triggers an event you can listen for application-wide.

## KoSIT validation

The module wires a console controller that runs:

```bash
vendor/bin/laminas xrechnung-kit:kosit data/xrechnung
```

Requires the `vineethkrishnan/xrechnung-kit-kosit-bundle` package and Java 17+.

## Testing

The module ships an integration test bootstrap under `adapters/laminas/tests/` that constructs a minimal MVC application with the module enabled.
