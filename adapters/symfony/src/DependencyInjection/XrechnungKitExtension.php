<?php

declare(strict_types=1);

namespace XrechnungKit\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use XrechnungKit\AtomicWriter;
use XrechnungKit\Logger\LoggerInterface;
use XrechnungKit\Logger\NullLogger;
use XrechnungKit\Notification\ChannelDispatcher;
use XrechnungKit\Notification\NotificationDispatcherInterface;
use XrechnungKit\Symfony\Logging\SymfonyLogger;
use XrechnungKit\XRechnungValidator;

/**
 * Registers the kit's services in the container with sensible defaults:
 * SymfonyLogger wrapping the application's PSR-3 logger, an empty Channel
 * Dispatcher for notifications, and the standard Validator + AtomicWriter.
 *
 * Consumers override any of these via the standard service-config mechanism
 * (decorator, alias) in their own config/services.yaml after the bundle
 * boots.
 */
final class XrechnungKitExtension extends Extension
{
    /** @param array<int, array<string, mixed>> $configs */
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->register(SymfonyLogger::class, SymfonyLogger::class)
            ->setArgument(0, new Reference('logger', ContainerBuilder::IGNORE_ON_INVALID_REFERENCE))
            ->setPublic(false);

        $container->setAlias(LoggerInterface::class, SymfonyLogger::class)
            ->setPublic(true);

        $container->register(NullLogger::class, NullLogger::class)
            ->setPublic(false);

        $container->register(ChannelDispatcher::class, ChannelDispatcher::class)
            ->setPublic(false);

        $container->setAlias(NotificationDispatcherInterface::class, ChannelDispatcher::class)
            ->setPublic(true);

        $container->register(XRechnungValidator::class, XRechnungValidator::class)
            ->setPublic(true);

        $container->register(AtomicWriter::class, AtomicWriter::class)
            ->setPublic(true);
    }

    #[\Override]
    public function getAlias(): string
    {
        return 'xrechnung_kit';
    }
}
