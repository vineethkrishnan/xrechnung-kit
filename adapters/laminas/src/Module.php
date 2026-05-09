<?php

declare(strict_types=1);

namespace XrechnungKit\Laminas;

/**
 * Classic Laminas MVC module entry point. Forwards the dependency
 * configuration from ConfigProvider so MVC applications get the same wiring
 * as Mezzio applications without duplicating definitions.
 *
 * Register in config/modules.config.php:
 *
 *   return [
 *       // ...
 *       'XrechnungKit\\Laminas',
 *   ];
 */
final class Module
{
    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        $configProvider = new ConfigProvider();
        return [
            'service_manager' => $configProvider->getDependencies(),
        ];
    }
}
