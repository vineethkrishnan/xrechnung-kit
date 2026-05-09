<?php

declare(strict_types=1);

namespace XrechnungKit\Symfony\Messenger;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\Logger\LoggerInterface;
use XrechnungKit\Notification\NotificationDispatcherInterface;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

#[AsMessageHandler]
final class GenerateXRechnungHandler
{
    public function __construct(
        private readonly XRechnungValidator $validator,
        private readonly LoggerInterface $logger,
        private readonly NotificationDispatcherInterface $notifications,
    ) {
    }

    public function __invoke(GenerateXRechnungMessage $message): string
    {
        $entity = XRechnungBuilder::buildEntity($message->mapping);

        $generator = new XRechnungGenerator(
            $entity,
            $this->validator,
            $this->logger,
            $this->notifications,
        );

        return $generator->generateXRechnung($message->targetPath);
    }
}
