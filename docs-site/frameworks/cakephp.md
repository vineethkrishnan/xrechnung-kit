# Using xrechnung-kit with CakePHP

The CakePHP adapter is a plugin that wires the framework-agnostic core into Cake's DI container, configuration, queue, and Cake-native logging / Slack alerting helpers.

This is the adapter that Locaboo (the original extraction source) consumes.

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-cakephp
```

Load the plugin in `Application::bootstrap()`:

```php
public function bootstrap(): void
{
    parent::bootstrap();
    $this->addPlugin(\XrechnungKit\CakePhp\Plugin::class);
}
```

## Configuration

`config/xrechnung_kit.php`:

```php
return [
    'XrechnungKit' => [
        'outputPath' => ROOT . DS . 'tmp' . DS . 'xrechnung',

        'logger'  => 'XrechnungKit',   // Cake log scope to use
        'alerter' => 'slack',          // built-in Slack alerter; null disables

        'kosit' => [
            'enabled'  => env('XRECHNUNG_KIT_KOSIT_ENABLED', false),
            'cacheDir' => env('XRECHNUNG_KIT_CACHE_DIR', CACHE . 'xrechnung-kit'),
        ],
    ],
];
```

## Generate an XRechnung

```php
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\CakePhp\Service\XrechnungKitService;

final class InvoicesController extends AppController
{
    public function generate(string $invoiceId): void
    {
        $service = $this->getTableLocator()->get('XrechnungKit')->getService();
        // or via DI: inject XrechnungKitService directly

        $mapping = $this->mapperFor($invoiceId)->produce();
        $path    = $service->generateXRechnung(
            $mapping,
            ROOT . DS . 'tmp' . DS . 'xrechnung' . DS . "RE-$invoiceId.xml",
        );

        $this->set(compact('path'));
    }
}
```

## CakeLogger binding

The plugin ships `CakeLogger` (PSR-3 over `Cake\Log\Log`) and binds it to `LoggerInterface`. The configured log scope receives the validator pipeline events.

## Slack alerter

The plugin ships `SlackAlerter`, a thin client that posts to a Slack incoming-webhook URL via the configured queue or directly. Configure under `XrechnungKit.slack.webhook`.

The dedup wrapper (provided by core) ensures the same error signature does not flood the channel within a 30-minute window.

## Queueable command

For heavy generation, dispatch via Cake's queue:

```bash
bin/cake queue worker
```

The plugin registers a `XrechnungKit.GenerateXRechnungJob`. Enqueue from your controller / shell.

## KoSIT validation

```bash
bin/cake xrechnung_kit kosit tmp/xrechnung
```

The shell command is registered by the plugin. Requires the `vineethkrishnan/xrechnung-kit-kosit-bundle` package and Java 17+.

## Migrating from a hand-rolled CakeLogger / SlackAlerter

If you already have CakePHP-native versions of these inside your application (as Locaboo did pre-extraction), replace them with the plugin's versions. The interfaces are stable; the wiring is the only thing that changes.

## Testing

The plugin ships an integration test app under `adapters/cakephp/tests/test_app/` for booting the plugin in your own integration tests.
