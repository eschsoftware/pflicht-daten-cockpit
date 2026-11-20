<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Administration;

use EschSoftware\PflichtDatenCockpit\Core\CatalogGap;
use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerScanner;
use EschSoftware\PflichtDatenCockpit\Core\Rule\RuleFinding;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
final class GpsrManufacturerController extends AbstractController
{
    public function __construct(private readonly GpsrManufacturerScanner $scanner)
    {
    }

    #[Route(
        path: '/api/_action/esch-pflicht-daten-cockpit/gpsr-manufacturer',
        name: 'api.action.esch_pflicht_daten_cockpit.gpsr_manufacturer',
        defaults: ['_acl' => ['esch_pflicht_daten_cockpit:read']],
        methods: ['GET'],
    )]
    public function list(Request $request, Context $context): JsonResponse
    {
        $status = $request->query->getString('status', 'all');

        if (!\in_array($status, ['all', 'open', 'ok'], true)) {
            return new JsonResponse([
                'error' => 'status must be all, open or ok',
            ], Response::HTTP_BAD_REQUEST);
        }

        $term = trim($request->query->getString('q'));

        if (mb_strlen($term) > 80) {
            return new JsonResponse([
                'error' => 'q must be at most 80 characters',
            ], Response::HTTP_BAD_REQUEST);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = $request->query->getInt('limit', 25);

        if ($limit < 1 || $limit > 100) {
            return new JsonResponse([
                'error' => 'limit must be between 1 and 100',
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->scanner->scan(
            $context,
            $page,
            $limit,
            $status,
            $term === '' ? null : $term,
        )->toArray());
    }

    #[Route(
        path: '/api/_action/esch-pflicht-daten-cockpit/gpsr-manufacturer/check',
        name: 'api.action.esch_pflicht_daten_cockpit.gpsr_manufacturer_check',
        defaults: ['_acl' => ['esch_pflicht_daten_cockpit:read']],
        methods: ['GET'],
    )]
    public function check(Request $request, Context $context): JsonResponse
    {
        $productId = trim($request->query->getString('productId'));
        $manufacturerId = trim($request->query->getString('manufacturerId'));

        if (($productId === '') === ($manufacturerId === '')) {
            return new JsonResponse([
                'error' => 'pass exactly one of productId or manufacturerId',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($productId !== '') {
            $gap = $this->scanner->inspectIds([$productId], $context)[0] ?? null;
            if (!$gap instanceof CatalogGap) {
                return new JsonResponse(['error' => 'product not found'], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'missing' => $gap->missing,
                'status' => $gap->status,
            ]);
        }

        $finding = $this->scanner->findingForManufacturer($manufacturerId, $context);
        if (!$finding instanceof RuleFinding) {
            return new JsonResponse(['error' => 'manufacturer not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'missing' => $finding->missing,
            'status' => $finding->status,
        ]);
    }
}
