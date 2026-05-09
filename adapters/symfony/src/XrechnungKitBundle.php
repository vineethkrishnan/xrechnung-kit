<?php

declare(strict_types=1);

namespace XrechnungKit\Symfony;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use XrechnungKit\Symfony\DependencyInjection\XrechnungKitExtension;

/**
 * Symfony bundle entry point. Register in config/bundles.php:
 *
 *   return [
 *       // ...
 *       XrechnungKit\Symfony\XrechnungKitBundle::class => ['all' => true],
 *   ];
 *
 * The DI extension auto-loads via the standard XrechnungKitExtension class
 * naming, no manual override required.
 */
final class XrechnungKitBundle extends Bundle
{
    #[\Override]
    public function getContainerExtension(): ?XrechnungKitExtension
    {
        if ($this->extension === null) {
            $this->extension = new XrechnungKitExtension();
        }
        /** @var XrechnungKitExtension $extension */
        $extension = $this->extension;
        return $extension;
    }
}
