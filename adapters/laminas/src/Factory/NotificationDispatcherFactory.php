<?php

declare(strict_types=1);

namespace XrechnungKit\Laminas\Factory;

use Psr\Container\ContainerInterface;
use XrechnungKit\Notification\ChannelDispatcher;

final class NotificationDispatcherFactory
{
    public function __invoke(ContainerInterface $container): ChannelDispatcher
    {
        return new ChannelDispatcher();
    }
}
