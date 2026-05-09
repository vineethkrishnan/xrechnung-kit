<?php

declare(strict_types=1);

namespace XrechnungKit\Laminas;

use XrechnungKit\AtomicWriter;
use XrechnungKit\Laminas\Factory\AtomicWriterFactory;
use XrechnungKit\Laminas\Factory\LaminasLoggerFactory;
use XrechnungKit\Laminas\Factory\NotificationDispatcherFactory;
use XrechnungKit\Laminas\Factory\XRechnungValidatorFactory;
use XrechnungKit\Laminas\Logging\LaminasLogger;
use XrechnungKit\Logger\LoggerInterface;
use XrechnungKit\Notification\NotificationDispatcherInterface;
use XrechnungKit\XRechnungValidator;

/**
 * Mezzio-style config provider. Returns the dependency configuration the
 * laminas-servicemanager uses to resolve the kit's services.
 *
 * Mezzio applications register this class in config/config.php; classic
 * Laminas MVC applications get the same wiring via the Module class which
 * forwards getServiceConfig() here.
 */
final class ConfigProvider
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /** @return array<string, array<string, string>> */
    public function getDependencies(): array
    {
        return [
            'aliases' => [
                LoggerInterface::class => LaminasLogger::class,
                NotificationDispatcherInterface::class => 'XrechnungKit\\Notification\\ChannelDispatcher',
            ],
            'factories' => [
                LaminasLogger::class => LaminasLoggerFactory::class,
                'XrechnungKit\\Notification\\ChannelDispatcher' => NotificationDispatcherFactory::class,
                XRechnungValidator::class => XRechnungValidatorFactory::class,
                AtomicWriter::class => AtomicWriterFactory::class,
            ],
        ];
    }
}
