<?php

declare(strict_types=1);

namespace XrechnungKit\CakePhp;

use Cake\Log\Log;
use XrechnungKit\Logger\LoggerInterface;

/**
 * Bridges core's LoggerInterface to Cake's static Log facade.
 *
 * Pass an instance to the XRechnungGenerator constructor (or wrap it in a
 * core LogChannel and add it to a ChannelDispatcher) to route the validator
 * pipeline events through your CakePHP application's configured log scopes.
 */
final class CakeLogger implements LoggerInterface
{
    /**
     * @param list<string> $scope Cake log scopes to tag every record with.
     */
    public function __construct(private readonly array $scope = ['xrechnung'])
    {
    }

    public function info(string $message, array $context = []): void
    {
        Log::info($message, $this->mergeScope($context));
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $this->mergeScope($context));
    }

    public function error(string $message, array $context = []): void
    {
        Log::error($message, $this->mergeScope($context));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function mergeScope(array $context): array
    {
        $existing = isset($context['scope']) ? (array) $context['scope'] : [];
        $context['scope'] = array_values(array_unique(array_merge($this->scope, $existing)));
        return $context;
    }
}
