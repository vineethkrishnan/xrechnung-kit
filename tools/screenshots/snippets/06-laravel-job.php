<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use XrechnungKit\Builder\XRechnungBuilder;
use XrechnungKit\XRechnungGenerator;
use XrechnungKit\XRechnungValidator;

class GenerateXRechnungJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}

    public function handle(): void
    {
        $mapping = (new InvoiceToMappingData($this->invoice))->produce();

        $entity = XRechnungBuilder::buildEntity($mapping);
        $path = (new XRechnungGenerator($entity))->generateXRechnung(
            storage_path("xrechnung/{$this->invoice->number}.xml"),
        );

        $validator = new XRechnungValidator();
        if (!$validator->validate($path)) {
            $this->fail(new \RuntimeException(
                'XRechnung XSD-invalid: ' . implode(', ', $validator->getErrors())
            ));
        }
    }
}
