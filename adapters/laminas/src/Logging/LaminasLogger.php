<?php

declare(strict_types=1);

namespace XrechnungKit\Laminas\Logging;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use XrechnungKit\Logger\LoggerInterface;

/**
 * Bridges the kit's LoggerInterface to a PSR-3 logger provided by the
 * application (laminas-log via its PsrLoggerAdapter, monolog, or any other
 * PSR-3 implementation registered in the service manager).
 */
final class LaminasLogger implements LoggerInterface
{
    public function __construct(private readonly PsrLoggerInterface $psr)
    {
    }

    /** @param array<string, mixed> $context */
    #[\Override]
    public function info(string $message, array $context = []): void
    {
        $this->psr->info($message, $context);
    }

    /** @param array<string, mixed> $context */
    #[\Override]
    public function warning(string $message, array $context = []): void
    {
        $this->psr->warning($message, $context);
    }

    /** @param array<string, mixed> $context */
    #[\Override]
    public function error(string $message, array $context = []): void
    {
        $this->psr->error($message, $context);
    }
}
