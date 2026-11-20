<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Command;

use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerFields;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\Tax\TaxCollection;
use Shopware\Core\System\Tax\TaxEntity;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'esch-pdc:seed-gaps',
    description: 'Legt Demo-Artikel mit vollständigen und fehlenden GPSR-Herstellerangaben an.',
)]
final class SeedGapsCommand extends Command
{
    /**
     * @param EntityRepository<\Shopware\Core\Content\Product\ProductCollection> $productRepository
     * @param EntityRepository<\Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerCollection> $manufacturerRepository
     * @param EntityRepository<TaxCollection> $taxRepository
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $manufacturerRepository,
        private readonly EntityRepository $taxRepository,
        private readonly EntityRepository $salesChannelRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $this->deletePrefixed($this->productRepository, 'productNumber', 'PDC-GPSR-', $context);
        $this->deletePrefixed($this->manufacturerRepository, 'name', 'PDC-GPSR ', $context);

        $tax = $this->taxRepository->search(new Criteria(), $context)->first();

        if (!$tax instanceof TaxEntity) {
            $io->error('Keine Steuer gefunden. Shopware muss installiert sein.');

            return Command::FAILURE;
        }

        $channelCriteria = new Criteria();
        $channelCriteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        $channelIds = [];

        foreach ($this->salesChannelRepository->searchIds($channelCriteria, $context)->getIds() as $channelId) {
            if (\is_string($channelId)) {
                $channelIds[] = $channelId;
            }
        }

        if ($channelIds === []) {
            $io->error('Kein Storefront-Sales-Channel gefunden.');

            return Command::FAILURE;
        }

        $completeId = Uuid::randomHex();
        $missingAddressId = Uuid::randomHex();
        $missingContactId = Uuid::randomHex();
        $address = "Musterweg 12\n50667 Köln\nDeutschland";

        $this->manufacturerRepository->create([
            [
                'id' => $completeId,
                'name' => 'PDC-GPSR Vollständig GmbH',
                'customFields' => [
                    GpsrManufacturerFields::ADDRESS => $address,
                    GpsrManufacturerFields::CONTACT => 'kontakt@pdc-example.test',
                ],
            ],
            [
                'id' => $missingAddressId,
                'name' => 'PDC-GPSR Ohne Anschrift GmbH',
                'customFields' => [
                    GpsrManufacturerFields::CONTACT => 'qualitaet@pdc-example.test',
                ],
            ],
            [
                'id' => $missingContactId,
                'name' => 'PDC-GPSR Ohne Kontakt GmbH',
                'customFields' => [
                    GpsrManufacturerFields::ADDRESS => $address,
                ],
            ],
        ], $context);

        $gross = 49.0;
        $net = round($gross / (1 + ($tax->getTaxRate() / 100)), 2);
        $products = [
            $this->product('PDC-GPSR-OK', 'Bohrmaschine vollständig', $completeId, true, $tax->getId(), $gross, $net, $channelIds),
            $this->product('PDC-GPSR-NO-MANUFACTURER', 'Säge ohne Hersteller', null, true, $tax->getId(), $gross, $net, $channelIds),
            $this->product('PDC-GPSR-NO-ADDRESS', 'Lampe ohne Anschrift', $missingAddressId, true, $tax->getId(), $gross, $net, $channelIds),
            $this->product('PDC-GPSR-NO-EMAIL', 'Kabel ohne Kontakt', $missingContactId, true, $tax->getId(), $gross, $net, $channelIds),
            $this->product('PDC-GPSR-INACTIVE', 'Inaktiver Artikel', $completeId, false, $tax->getId(), $gross, $net, $channelIds),
        ];

        $this->productRepository->create($products, $context);

        $io->success('GPSR-Demodaten stehen.');
        $io->listing([
            'PDC-GPSR-OK ist vollständig und bestellbar.',
            'PDC-GPSR-NO-MANUFACTURER, PDC-GPSR-NO-ADDRESS und PDC-GPSR-NO-EMAIL sperren den Checkout.',
            'PDC-GPSR-INACTIVE erscheint nicht in der Übersicht.',
        ]);

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $channelIds
     *
     * @return array<string, mixed>
     */
    private function product(
        string $number,
        string $name,
        ?string $manufacturerId,
        bool $active,
        string $taxId,
        float $gross,
        float $net,
        array $channelIds,
    ): array {
        $visibilities = [];

        foreach ($channelIds as $channelId) {
            $visibilities[] = [
                'id' => Uuid::randomHex(),
                'salesChannelId' => $channelId,
                'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
            ];
        }

        return [
            'id' => Uuid::randomHex(),
            'name' => $name,
            'productNumber' => $number,
            'stock' => 25,
            'taxId' => $taxId,
            'active' => $active,
            'manufacturerId' => $manufacturerId,
            'price' => [[
                'currencyId' => Defaults::CURRENCY,
                'gross' => $gross,
                'net' => $net,
                'linked' => false,
            ]],
            'visibilities' => $visibilities,
        ];
    }

    private function deletePrefixed(EntityRepository $repository, string $field, string $prefix, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new PrefixFilter($field, $prefix));
        $criteria->setLimit(500);
        $payload = [];

        foreach ($repository->searchIds($criteria, $context)->getIds() as $id) {
            $payload[] = \is_array($id) ? $id : ['id' => $id];
        }

        if ($payload !== []) {
            $repository->delete($payload, $context);
        }
    }
}
