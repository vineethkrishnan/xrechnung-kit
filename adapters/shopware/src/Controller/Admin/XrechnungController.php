<?php

declare(strict_types=1);

namespace Vineethkrishnan\XrechnungKitShopware\Controller\Admin;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Vineethkrishnan\XrechnungKitShopware\Core\Content\XrechnungInvoice\XrechnungInvoiceEntity;

/**
 * Admin-only HTTP endpoints used by the Shopware admin SPA.
 *
 * Listing and per-record reads are already available via the auto-generated
 * /api/xrechnung-kit-invoice DAL routes; this controller only adds the
 * download endpoint, which streams the xml from the filesystem rather
 * than from the DAL row.
 *
 * The xrechnung_kit_invoice entity respects standard ACL through the DAL,
 * so the admin permission for read access is xrechnung_kit_invoice:read.
 */
#[Route(defaults: ['_routeScope' => ['api']])]
final class XrechnungController
{
    public function __construct(
        private readonly EntityRepository $xrechnungInvoiceRepository,
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
}
