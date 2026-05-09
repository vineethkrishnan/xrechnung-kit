<?php

declare(strict_types=1);

namespace XrechnungKit\Laminas\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Psr\Log\NullLogger as PsrNullLogger;
use XrechnungKit\Laminas\Logging\LaminasLogger;

/**
 * Resolves a PSR-3 logger from the service manager and wraps it in
 * LaminasLogger. Falls back to PSR-3 NullLogger when no PSR-3 logger is
 * registered so the kit works in container configurations without one.
 */
final class LaminasLoggerFactory
{
    public function __invoke(ContainerInterface $container): LaminasLogger
    {
        if ($container->has(PsrLoggerInterface::class)) {
            /** @var PsrLoggerInterface $psr */
            $psr = $container->get(PsrLoggerInterface::class);
            return new LaminasLogger($psr);
        }
        return new LaminasLogger(new PsrNullLogger());
    }
}
