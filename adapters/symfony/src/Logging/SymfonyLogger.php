<?php

declare(strict_types=1);

namespace XrechnungKit\Symfony\Logging;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use XrechnungKit\Logger\LoggerInterface;

/**
 * Bridges the kit's LoggerInterface to Symfony's monolog (PSR-3) logger.
 * The constructor accepts a nullable PSR-3 logger so the bundle works in
 * environments where monolog is not installed; null falls through to
 * silent no-ops.
 */
final class SymfonyLogger implements LoggerInterface
{
    public function __construct(private readonly ?PsrLoggerInterface $psr = null)
    {
    }

    /** @param array<string, mixed> $context */
    #[\Override]
    public function info(string $message, array $context = []): void
    {
        $this->psr?->info($message, $context);
    }

    /** @param array<string, mixed> $context */
    #[\Override]
    public function warning(string $message, array $context = []): void
    {
        $this->psr?->warning($message, $context);
    }

    /** @param array<string, mixed> $context */
    #[\Override]
    public function error(string $message, array $context = []): void
    {
        $this->psr?->error($message, $context);
    }
}
