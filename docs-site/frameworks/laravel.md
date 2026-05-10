# Using xrechnung-kit with Laravel

The Laravel adapter wires the framework-agnostic core into Laravel's container, config, queue, and notifications stack.

## Install

```bash
composer require vineethkrishnan/xrechnung-kit-laravel
```

The service provider auto-registers via package discovery (Laravel 11+).

## Publish config

```bash
php artisan vendor:publish --tag=xrechnung-kit-config
```

This drops `config/xrechnung-kit.php`:

```php
return [
    'output_path' => storage_path('app/xrechnung'),

    'logger'  => 'stack',         // any PSR-3 logger channel
    'alerter' => 'slack',         // notification channel name; null disables

    'kosit' => [
        'enabled'   => env('XRECHNUNG_KIT_KOSIT_ENABLED', false),
        'cache_dir' => env('XRECHNUNG_KIT_CACHE_DIR', storage_path('app/xrechnung-cache')),
    ],
];
```

## Generate an XRechnung

```php
use XrechnungKit\Laravel\Facades\XrechnungKit;

$path = XrechnungKit::generateForMapping($myMapper)->getPath();
// $myMapper: any SourceMapperInterface implementation
```

Or via the container:

```php
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\XRechnungGenerator;

public function __invoke(InvoiceController $controller, XRechnungGenerator $generator)
{
    $entity = XRechnungBuilder::buildEntity($controller->mapper()->produce());
    $path   = $generator->generateXRechnung(storage_path('app/xrechnung/RE-1.xml'));
}
```

## Queueable job

For heavy or background generation, dispatch the bundled job:

```php
use XrechnungKit\Laravel\Jobs\GenerateXRechnungJob;

GenerateXRechnungJob::dispatch($myMapper, storage_path('app/xrechnung/RE-1.xml'))
    ->onQueue('invoices');
```

The job:

1. Resolves the configured logger / alerter from the container.
2. Runs the same `MappingData` -> entity -> generator -> validator -> writer pipeline.
3. On success, dispatches `XRechnungGenerated` event with the path.
4. On failure, dispatches `XRechnungFailedValidation` with the error list and the quarantined `*_invalid.xml` path.

## Logger binding

Any PSR-3 logger channel works. The adapter wires Laravel's `Illuminate\Log\Logger` (which is PSR-3) by name.

## Alerter binding

The adapter ships a `LaravelNotificationAlerter` that maps `AlerterInterface::notify($message, $channel)` to Laravel's notification system. Configure the notifiable in `config/xrechnung-kit.php`.

## KoSIT validation

Run as an artisan command (registered by the adapter):

```bash
php artisan xrechnung-kit:kosit storage/app/xrechnung
```

Requires the `vineethkrishnan/xrechnung-kit-kosit-bundle` package and Java 17+ on the host.

## Testing

Use Laravel's `Storage::fake()` and assert on the written file:

```php
Storage::fake('local');

GenerateXRechnungJob::dispatchSync($myMapper, storage_path('app/xrechnung/RE-1.xml'));

Storage::assertExists('xrechnung/RE-1.xml');
```

For full pipeline coverage, the adapter ships a Pest / PHPUnit integration test app under `adapters/laravel/tests/Application` that boots a minimal Laravel kernel.
