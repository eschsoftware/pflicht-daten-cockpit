<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class GpsrManufacturerFieldInstaller
{
    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     */
    public function __construct(private readonly EntityRepository $customFieldSetRepository)
    {
    }

    public function ensureFields(Context $context): void
    {
        if ($this->findSetId($context) !== null) {
            return;
        }

        $this->customFieldSetRepository->create([[
            'name' => GpsrManufacturerFields::SET_NAME,
            'config' => [
                'label' => [
                    'de-DE' => 'GPSR-Herstellerangaben',
                    'en-GB' => 'GPSR manufacturer details',
                ],
            ],
            'relations' => [
                ['entityName' => 'product_manufacturer'],
            ],
            'customFields' => [
                [
                    'name' => GpsrManufacturerFields::ADDRESS,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Postanschrift',
                            'en-GB' => 'Postal address',
                        ],
                        'customFieldType' => CustomFieldTypes::TEXT,
                        'customFieldPosition' => 1,
                        'componentName' => 'sw-textarea-field',
                    ],
                ],
                [
                    'name' => GpsrManufacturerFields::CONTACT,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Elektronischer Kontakt (E-Mail)',
                            'en-GB' => 'Electronic contact (email)',
                        ],
                        'customFieldType' => CustomFieldTypes::TEXT,
                        'customFieldPosition' => 2,
                        'componentName' => 'sw-field',
                    ],
                ],
            ],
        ]], $context);
    }

    public function removeFields(Context $context): void
    {
        $id = $this->findSetId($context);

        if ($id === null) {
            return;
        }

        $this->customFieldSetRepository->delete([['id' => $id]], $context);
    }

    private function findSetId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', GpsrManufacturerFields::SET_NAME));
        $criteria->setLimit(1);
        $id = $this->customFieldSetRepository->searchIds($criteria, $context)->firstId();

        return \is_string($id) && $id !== '' ? $id : null;
    }
}
