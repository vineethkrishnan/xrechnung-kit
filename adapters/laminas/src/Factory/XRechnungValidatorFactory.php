<?php

declare(strict_types=1);

namespace XrechnungKit\Laminas\Factory;

use Psr\Container\ContainerInterface;
use XrechnungKit\XRechnungValidator;

final class XRechnungValidatorFactory
{
    public function __invoke(ContainerInterface $container): XRechnungValidator
    {
        return new XRechnungValidator();
    }
}
