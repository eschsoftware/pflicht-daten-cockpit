<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core;

use EschSoftware\PflichtDatenCockpit\Core\Rule\GpsrManufacturerRule;
use EschSoftware\PflichtDatenCockpit\Core\Rule\ProductCheckInput;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class GpsrManufacturerScanner
{
    private const BATCH = 200;

    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly GpsrManufacturerRule $rule,
    ) {
    }

    public function scan(Context $context, int $page, int $limit, string $status, ?string $term): ScanPage
    {
        $context = $this->readingContext($context);
        $counts = ['checked' => 0, 'open' => 0, 'ok' => 0];
        $matched = [];
        $offset = 0;

        do {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('active', true));
            $criteria->addFilter(new EqualsFilter('childCount', 0));
            $criteria->addAssociation('manufacturer');
            $criteria->addSorting(new FieldSorting('productNumber', FieldSorting::ASCENDING));
            $criteria->setLimit(self::BATCH);
            $criteria->setOffset($offset);
            $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);

            $products = $this->productRepository->search($criteria, $context);
            foreach ($products->getEntities() as $product) {
                if (!$product instanceof ProductEntity) {
                    continue;
                }

                $gap = $this->toGap($product);
                ++$counts['checked'];
                ++$counts[$gap->status];

                if ($status !== 'all' && $gap->status !== $status) {
                    continue;
                }

                if ($term !== null && !$this->matches($gap, $term)) {
                    continue;
                }

                $matched[] = $gap;
            }

            $fetched = $products->count();
            $offset += self::BATCH;
        } while ($fetched === self::BATCH);

        $total = \count($matched);
        $items = \array_slice($matched, ($page - 1) * $limit, $limit);

        return new ScanPage($counts, array_values($items), $page, $limit, $total);
    }

    /**
     * @param list<string> $productIds
     *
     * @return list<CatalogGap>
     */
    public function inspectIds(array $productIds, Context $context): array
    {
        $productIds = array_values(array_unique(array_filter($productIds, static fn (string $id): bool => $id !== '')));

        if ($productIds === []) {
            return [];
        }

        $context = $this->readingContext($context);
        $criteria = new Criteria($productIds);
        $criteria->addAssociation('manufacturer');
        $products = $this->productRepository->search($criteria, $context);
        $gaps = [];

        foreach ($productIds as $productId) {
            $product = $products->get($productId);

            if (!$product instanceof ProductEntity) {
                continue;
            }

            $gaps[] = $this->toGap($product);
        }

        return $gaps;
    }

    private function toGap(ProductEntity $product): CatalogGap
    {
        $manufacturer = $product->getManufacturer();
        $manufacturerName = null;
        $address = null;
        $contact = null;

        if ($manufacturer instanceof ProductManufacturerEntity) {
            $rawName = $manufacturer->getName();
            $manufacturerName = \is_string($rawName) ? trim($rawName) : null;
            if ($manufacturerName === '') {
                $manufacturerName = null;
            }

            $customFields = $manufacturer->getCustomFields() ?? [];
            $address = $this->stringOrNull($customFields[GpsrManufacturerFields::ADDRESS] ?? null);
            $contact = $this->stringOrNull($customFields[GpsrManufacturerFields::CONTACT] ?? null);
        }

        $finding = $this->rule->evaluate(new ProductCheckInput(
            $product->getManufacturerId() !== null || $manufacturer instanceof ProductManufacturerEntity,
            $manufacturerName,
            $address,
            $contact,
        ));

        $rawProductName = $product->getName();
        $productName = \is_string($rawProductName) ? trim($rawProductName) : '';
        if ($productName === '') {
            $productName = $product->getProductNumber();
        }

        return new CatalogGap(
            $product->getId(),
            $product->getProductNumber(),
            $productName,
            $manufacturerName,
            $finding->missing,
            $finding->status,
        );
    }

    private function matches(CatalogGap $gap, string $term): bool
    {
        $haystack = mb_strtolower($gap->productNumber . ' ' . $gap->productName . ' ' . ($gap->manufacturerName ?? ''));

        return mb_strpos($haystack, mb_strtolower($term)) !== false;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return \is_string($value) ? $value : null;
    }

    private function readingContext(Context $context): Context
    {
        $context = clone $context;
        $context->setConsiderInheritance(true);

        return $context;
    }
}
