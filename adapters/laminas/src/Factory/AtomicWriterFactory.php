<?php

declare(strict_types=1);

namespace XrechnungKit\Laminas\Factory;

use Psr\Container\ContainerInterface;
use XrechnungKit\AtomicWriter;

final class AtomicWriterFactory
{
    public function __invoke(ContainerInterface $container): AtomicWriter
    {
        return new AtomicWriter();
    }
}
