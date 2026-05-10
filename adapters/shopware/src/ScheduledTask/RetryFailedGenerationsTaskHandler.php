<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\ScheduledTask;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceEntity;
use Vineethkrishnan\XrechnungKitShopware\Service\GenerationOrchestrator;

/**
 * Handler for RetryFailedGenerationsTask.
 *
 * Iterates xrechnung_kit_invoice rows where status is failed or
 * invalid AND attempt_count is below MAX_ATTEMPTS, and asks the
 * GenerationOrchestrator to try again. Retries are tagged with
 * triggered_via = scheduled_retry so the audit trail distinguishes
 * them from manual operator actions and the original order-state
 * trigger.
 *
 * After MAX_ATTEMPTS the row is left alone for an operator to triage
 * via the quarantine list view.
 */
#[AsMessageHandler(handles: RetryFailedGenerationsTask::class)]
final class RetryFailedGenerationsTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly EntityRepository $xrechnungInvoiceRepository,
        private readonly GenerationOrchestrator $orchestrator,
        private readonly LoggerInterface $taskLogger,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public static function getHandledMessages(): iterable
    {
        return [RetryFailedGenerationsTask::class];
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();

        $criteria = (new Criteria())
            ->addFilter(new EqualsAnyFilter('status', [
                XrechnungInvoiceDefinition::STATUS_FAILED,
                XrechnungInvoiceDefinition::STATUS_INVALID,
            ]))
            ->addFilter(new RangeFilter('attemptCount', [
                RangeFilter::LT => RetryFailedGenerationsTask::MAX_ATTEMPTS,
            ]))
            ->setLimit(50);

        $candidates = $this->xrechnungInvoiceRepository->search($criteria, $context);

        $this->taskLogger->info('xrechnung-kit-shopware: retry sweep starting', [
            'candidateCount' => $candidates->count(),
        ]);

        foreach ($candidates as $invoice) {
            if (!$invoice instanceof XrechnungInvoiceEntity) {
                continue;
            }
            try {
                $this->orchestrator->generateForOrder(
                    $invoice->getOrderId(),
                    $context,
                    XrechnungInvoiceDefinition::TRIGGER_SCHEDULED_RETRY,
                    null,
                );
            } catch (\Throwable $e) {
                $this->taskLogger->error('xrechnung-kit-shopware: retry sweep entry failed', [
                    'invoiceId' => $invoice->getId(),
                    'orderId' => $invoice->getOrderId(),
                    'attempt' => $invoice->getAttemptCount(),
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->taskLogger->info('xrechnung-kit-shopware: retry sweep finished');
    }
}
