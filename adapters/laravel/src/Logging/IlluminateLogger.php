<?php

declare(strict_types=1);

namespace XrechnungKit\Laravel\Logging;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use XrechnungKit\Logger\LoggerInterface;

/**
 * Bridges the kit's LoggerInterface to any PSR-3 logger Laravel exposes
 * (Monolog, the array driver, the stack channel, ...). Inject the channel
 * you want by name from config/logging.php; the service provider resolves it
 * once at boot time.
 */
final class IlluminateLogger implements LoggerInterface
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
