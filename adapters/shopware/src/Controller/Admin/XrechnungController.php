<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Controller\Admin;

use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceDefinition;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceEntity;
use Vineethkrishnan\XrechnungKitShopware\Service\GenerationOrchestrator;

/**
 * Admin-only HTTP endpoints used by the Shopware admin SPA.
 *
 * Listing and per-record reads use the auto-generated DAL routes at
 * /api/xrechnung-kit-invoice. This controller handles the two actions
 * the DAL routes cannot model:
 *
 *   GET  /api/_action/xrechnung-kit/download/{invoiceId}
 *        Streams the generated XML from disk.
 *
 *   POST /api/_action/xrechnung-kit/regenerate/{orderId}
 *        Triggers a fresh generation for an order. Used by the
 *        "Regenerate now" button on the order detail XRechnung tab.
 *
 * Both endpoints are ACL-gated.
 */
#[Route(defaults: ['_routeScope' => ['api']])]
final class XrechnungController
{
    public function __construct(
        private readonly EntityRepository $xrechnungInvoiceRepository,
        private readonly GenerationOrchestrator $orchestrator,
    ) {
    }

    #[Route(
        path: '/api/_action/xrechnung-kit/download/{invoiceId}',
        name: 'api.action.xrechnung-kit.download',
        defaults: [
            '_acl' => ['xrechnung_kit_invoice:read'],
        ],
        methods: ['GET']
    )]
    public function download(string $invoiceId, Context $context): BinaryFileResponse|JsonResponse
    {
        $criteria = new Criteria([$invoiceId]);
        $criteria->addAssociation('order');

        $invoice = $this->xrechnungInvoiceRepository->search($criteria, $context)->first();
        if (!$invoice instanceof XrechnungInvoiceEntity) {
            return new JsonResponse(['error' => 'XRechnung invoice not found'], 404);
        }

        $path = $invoice->getGeneratedPath();
        if ($path === null || $path === '' || !is_file($path) || !is_readable($path)) {
            return new JsonResponse([
                'error' => 'XRechnung file is missing on disk; regenerate from the order detail.',
            ], 410);
        }

        $invoiceFilename = $invoice->getOrder()?->getOrderNumber() ?? $invoice->getId();
        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf('%s.xml', $invoiceFilename),
        );
        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }

    #[Route(
        path: '/api/_action/xrechnung-kit/regenerate/{orderId}',
        name: 'api.action.xrechnung-kit.regenerate',
        defaults: [
            '_acl' => ['xrechnung_kit_invoice:update'],
        ],
        methods: ['POST']
    )]
    public function regenerate(string $orderId, Context $context): JsonResponse
    {
        $source = $context->getSource();
        $triggeredBy = $source instanceof AdminApiSource ? $source->getUserId() : null;

        $invoiceId = $this->orchestrator->generateForOrder(
            $orderId,
            $context,
            XrechnungInvoiceDefinition::TRIGGER_MANUAL,
            $triggeredBy,
        );

        // Reload to surface the result back to the admin UI.
        $invoice = $this->xrechnungInvoiceRepository->search(new Criteria([$invoiceId]), $context)->first();

        if (!$invoice instanceof XrechnungInvoiceEntity) {
            return new JsonResponse(
                ['error' => 'Generation finished but the invoice row could not be reloaded.'],
                500,
            );
        }

        return new JsonResponse([
            'id' => $invoice->getId(),
            'status' => $invoice->getStatus(),
            'errors' => $invoice->getErrors() ?? [],
            'generatedPath' => $invoice->getGeneratedPath(),
            'kositResult' => $invoice->getKositResult(),
            'attemptCount' => $invoice->getAttemptCount(),
            'triggeredVia' => $invoice->getTriggeredVia(),
            'triggeredBy' => $invoice->getTriggeredBy(),
        ]);
    }
}
