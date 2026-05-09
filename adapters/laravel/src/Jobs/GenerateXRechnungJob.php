<?php

declare(strict_types=1);

namespace XrechnungKit\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\Logger\LoggerInterface;
use XrechnungKit\Mapping\MappingData;
use XrechnungKit\Notification\NotificationDispatcherInterface;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

/**
 * Queueable wrapper around the full kit pipeline. Dispatch with a fully
 * constructed MappingData and a target file path; the job runs Builder ->
 * Generator on the configured queue and lands the XML on disk via the
 * standard validate-before-write + quarantine semantics.
 *
 * The MappingData VO graph is serialisable through Laravel's queue payload
 * because every VO is a readonly value object with no framework references.
 */
final class GenerateXRechnungJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly MappingData $mapping,
        private readonly string $targetPath,
    ) {
    }

    public function handle(
        XRechnungValidator $validator,
        LoggerInterface $logger,
        NotificationDispatcherInterface $notifications,
    ): string {
        $entity = XRechnungBuilder::buildEntity($this->mapping);

        $generator = new XRechnungGenerator(
            $entity,
            $validator,
            $logger,
            $notifications,
        );

        return $generator->generateXRechnung($this->targetPath);
    }
}
