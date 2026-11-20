<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core;

use EschSoftware\PflichtDatenCockpit\Core\Rule\GpsrManufacturerRule;
use EschSoftware\PflichtDatenCockpit\Core\Rule\ProductCheckInput;
use EschSoftware\PflichtDatenCockpit\Core\Rule\RuleFinding;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerCollection;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class GpsrManufacturerScanner
{
    private const BATCH = 200;

    /**
     * @param EntityRepository<ProductCollection> $productRepository
     * @param EntityRepository<ProductManufacturerCollection> $manufacturerRepository
     */
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $manufacturerRepository,
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
            $criteria = $this->catalogCriteria();
            $criteria->addSorting(new FieldSorting('productNumber', FieldSorting::ASCENDING));
            $criteria->setLimit(self::BATCH);
            $criteria->setOffset($offset);
            $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_NONE);

            $products = $this->productRepository->search($criteria, $context);
            $parents = $this->loadParents($products->getEntities(), $context);
            foreach ($products->getEntities() as $product) {
                if (!$product instanceof ProductEntity) {
                    continue;
                }

                $gap = $this->toGap($product, $this->parentOf($product, $parents));
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
        $parents = $this->loadParents($products->getEntities(), $context);
        $gaps = [];

        foreach ($productIds as $productId) {
            $product = $products->get($productId);

            if (!$product instanceof ProductEntity) {
                continue;
            }

            $gaps[] = $this->toGap($product, $this->parentOf($product, $parents));
        }

        return $gaps;
    }

    public function findingForManufacturer(string $manufacturerId, Context $context): ?RuleFinding
    {
        $manufacturer = $this->manufacturerRepository->search(new Criteria([$manufacturerId]), $context)->get($manufacturerId);

        if (!$manufacturer instanceof ProductManufacturerEntity) {
            return null;
        }

        return $this->rule->evaluate(new ProductCheckInput(
            true,
            $this->textOrNull($manufacturer->getName()),
            $this->customField($manufacturer, GpsrManufacturerFields::ADDRESS),
            $this->customField($manufacturer, GpsrManufacturerFields::CONTACT),
        ));
    }

    private function catalogCriteria(): Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        // Varianten (parentId gesetzt), Artikel mit childCount 0 und Artikel,
        // deren childCount noch NULL ist. Varianten-Väter haben childCount > 0
        // und keine parentId, die fallen heraus.
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('parentId', null)]),
            new EqualsFilter('childCount', null),
            new EqualsFilter('childCount', 0),
        ]));
        $criteria->addAssociation('manufacturer');

        return $criteria;
    }

    /**
     * @param iterable<ProductEntity> $products
     *
     * @return array<string, ProductEntity>
     */
    private function loadParents(iterable $products, Context $context): array
    {
        $ids = [];
        foreach ($products as $product) {
            if ($product instanceof ProductEntity && \is_string($product->getParentId()) && $product->getParentId() !== '') {
                $ids[] = $product->getParentId();
            }
        }

        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        $criteria = new Criteria($ids);
        $criteria->addAssociation('manufacturer');
        $parents = [];
        foreach ($this->productRepository->search($criteria, $context)->getEntities() as $parent) {
            if ($parent instanceof ProductEntity) {
                $parents[$parent->getId()] = $parent;
            }
        }

        return $parents;
    }

    /**
     * @param array<string, ProductEntity> $parents
     */
    private function parentOf(ProductEntity $product, array $parents): ?ProductEntity
    {
        $parentId = $product->getParentId();
        if (!\is_string($parentId) || $parentId === '') {
            return null;
        }

        return $parents[$parentId] ?? null;
    }

    private function toGap(ProductEntity $product, ?ProductEntity $parent): CatalogGap
    {
        $manufacturer = $this->resolveManufacturer($product, $parent);
        $manufacturerName = null;
        $address = null;
        $contact = null;

        if ($manufacturer instanceof ProductManufacturerEntity) {
            $rawName = $manufacturer->getName();
            $manufacturerName = \is_string($rawName) ? trim($rawName) : null;
            if ($manufacturerName === '') {
                $manufacturerName = null;
            }

            $address = $this->customField($manufacturer, GpsrManufacturerFields::ADDRESS);
            $contact = $this->customField($manufacturer, GpsrManufacturerFields::CONTACT);
        }

        $finding = $this->rule->evaluate(new ProductCheckInput(
            $this->manufacturerAssigned($product, $manufacturer, $parent),
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

    private function resolveManufacturer(ProductEntity $product, ?ProductEntity $parent): ?ProductManufacturerEntity
    {
        $manufacturer = $product->getManufacturer();
        if ($manufacturer instanceof ProductManufacturerEntity) {
            return $manufacturer;
        }

        if ($parent instanceof ProductEntity && $parent->getManufacturer() instanceof ProductManufacturerEntity) {
            return $parent->getManufacturer();
        }

        return null;
    }

    private function manufacturerAssigned(ProductEntity $product, ?ProductManufacturerEntity $manufacturer, ?ProductEntity $parent): bool
    {
        if ($product->getManufacturerId() !== null || $manufacturer instanceof ProductManufacturerEntity) {
            return true;
        }

        return $parent instanceof ProductEntity && $parent->getManufacturerId() !== null;
    }

    private function customField(ProductManufacturerEntity $manufacturer, string $name): ?string
    {
        $customFields = $manufacturer->getCustomFields() ?? [];

        return $this->stringOrNull($customFields[$name] ?? null);
    }

    private function textOrNull(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
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
