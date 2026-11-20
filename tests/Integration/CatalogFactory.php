<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Test\Integration;

use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerFields;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Tax\TaxEntity;

final class CatalogFactory
{
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $manufacturerRepository,
        private readonly EntityRepository $taxRepository,
    ) {
    }

    /**
     * @param array<string, string|null> $customFields
     */
    public function manufacturer(Context $context, string $name, array $customFields = []): string
    {
        $id = Uuid::randomHex();
        $payload = [
            'id' => $id,
            'name' => $name,
        ];

        $fields = [];
        foreach ($customFields as $key => $value) {
            if ($value !== null) {
                $fields[$key] = $value;
            }
        }

        if ($fields !== []) {
            $payload['customFields'] = $fields;
        }

        $this->manufacturerRepository->create([$payload], $context);

        return $id;
    }

    public function product(
        Context $context,
        string $salesChannelId,
        string $number,
        string $name,
        ?string $manufacturerId,
        bool $active = true,
    ): string {
        $taxId = $this->taxId($context);
        $tax = $this->taxRepository->search(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria([$taxId]), $context)->get($taxId);
        $rate = $tax instanceof TaxEntity ? $tax->getTaxRate() : 19.0;
        $gross = 19.0;
        $id = Uuid::randomHex();

        $this->productRepository->create([[
            'id' => $id,
            'name' => $name,
            'productNumber' => $number,
            'stock' => 10,
            'taxId' => $taxId,
            'active' => $active,
            'manufacturerId' => $manufacturerId,
            'price' => [[
                'currencyId' => Defaults::CURRENCY,
                'gross' => $gross,
                'net' => round($gross / (1 + ($rate / 100)), 2),
                'linked' => false,
            ]],
            'visibilities' => [[
                'id' => Uuid::randomHex(),
                'salesChannelId' => $salesChannelId,
                'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
            ]],
        ]], $context);

        return $id;
    }

    public function variant(Context $context, string $parentId, string $number): string
    {
        $childId = Uuid::randomHex();
        $groupId = Uuid::randomHex();
        $optionId = Uuid::randomHex();

        $this->productRepository->upsert([[
            'id' => $parentId,
            'configuratorSettings' => [[
                'option' => [
                    'id' => $optionId,
                    'name' => 'Standard',
                    'group' => [
                        'id' => $groupId,
                        'name' => 'PDC ' . substr($optionId, 0, 8),
                        'displayType' => 'text',
                        'sortingType' => 'alphanumeric',
                    ],
                ],
            ]],
        ]], $context);

        $this->productRepository->create([[
            'id' => $childId,
            'parentId' => $parentId,
            'productNumber' => $number,
            'stock' => 4,
            'active' => true,
            'options' => [['id' => $optionId]],
        ]], $context);

        return $childId;
    }

    public function taxId(Context $context): string
    {
        $id = $this->taxRepository->searchIds(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria(), $context)->firstId();

        if (!\is_string($id) || $id === '') {
            throw new \RuntimeException('No tax rate available.');
        }

        return $id;
    }

    public static function address(): string
    {
        return "Musterweg 12\n50667 Köln";
    }

    public static function contactField(): string
    {
        return GpsrManufacturerFields::CONTACT;
    }

    public static function addressField(): string
    {
        return GpsrManufacturerFields::ADDRESS;
    }
}
