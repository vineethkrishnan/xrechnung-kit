<?php

declare(strict_types=1);

namespace XrechnungKit\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use XrechnungKit\AtomicWriter;
use XrechnungKit\Laravel\Logging\IlluminateLogger;
use XrechnungKit\Logger\LoggerInterface;
use XrechnungKit\Logger\NullLogger;
use XrechnungKit\Notification\ChannelDispatcher;
use XrechnungKit\Notification\NotificationDispatcherInterface;
use XrechnungKit\XRechnungValidator;

/**
 * Auto-discovered via composer.json's extra.laravel.providers. Registers the
 * framework-agnostic kit services as singletons in Laravel's container with
 * sensible defaults: PSR-3 logger bridged via IlluminateLogger, empty
 * notification dispatcher, default validator, default atomic writer.
 *
 * Consumers override any of these by binding their own implementation in the
 * application's AppServiceProvider after this provider has run.
 */
final class XrechnungKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoggerInterface::class, function (Application $app): LoggerInterface {
            if (!$app->bound(PsrLoggerInterface::class)) {
                return new NullLogger();
            }
            $psr = $app->make(PsrLoggerInterface::class);
            return new IlluminateLogger($psr);
        });

        $this->app->singleton(NotificationDispatcherInterface::class, function (): NotificationDispatcherInterface {
            return new ChannelDispatcher();
        });

        $this->app->singleton(XRechnungValidator::class, function (): XRechnungValidator {
            return new XRechnungValidator();
        });

        $this->app->singleton(AtomicWriter::class, function (): AtomicWriter {
            return new AtomicWriter();
        });
    }
}
