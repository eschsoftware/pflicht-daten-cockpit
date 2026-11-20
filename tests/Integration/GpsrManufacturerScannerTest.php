<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Test\Integration;

use Doctrine\DBAL\Connection;
use EschSoftware\PflichtDatenCockpit\Administration\GpsrManufacturerController;
use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerDataCleaner;
use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerFieldInstaller;
use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerFields;
use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerScanner;
use EschSoftware\PflichtDatenCockpit\Core\Rule\GpsrManufacturerRule;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Symfony\Component\HttpFoundation\Request;

final class GpsrManufacturerScannerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testOpenProductsExposeTheMissingField(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = $this->storefrontSalesChannelId($context);
        $factory = $this->catalog();
        $prefix = 'PDC-IT-' . substr(Uuid::randomHex(), 0, 8);

        $complete = $factory->manufacturer($context, $prefix . ' komplett', [
            GpsrManufacturerFields::ADDRESS => CatalogFactory::address(),
            GpsrManufacturerFields::CONTACT => 'ok@pdc-example.test',
        ]);
        $noAddress = $factory->manufacturer($context, $prefix . ' ohne anschrift', [
            GpsrManufacturerFields::CONTACT => 'adresse@pdc-example.test',
        ]);
        $badMail = $factory->manufacturer($context, $prefix . ' schlechte mail', [
            GpsrManufacturerFields::ADDRESS => CatalogFactory::address(),
            GpsrManufacturerFields::CONTACT => 'nicht-gültig',
        ]);

        $factory->product($context, $salesChannelId, $prefix . '-OK', 'Vollständig', $complete);
        $factory->product($context, $salesChannelId, $prefix . '-ADDRESS', 'Ohne Anschrift', $noAddress);
        $factory->product($context, $salesChannelId, $prefix . '-MAIL', 'Ohne Mail', $badMail);
        $factory->product($context, $salesChannelId, $prefix . '-NONE', 'Ohne Hersteller', null);
        $factory->product($context, $salesChannelId, $prefix . '-OFF', 'Inaktiv', null, false);

        $open = $this->scanner()->scan($context, 1, 25, 'open', $prefix);
        $byNumber = [];
        foreach ($open->items as $item) {
            $byNumber[$item->productNumber] = $item->missing;
        }

        self::assertSame([GpsrManufacturerRule::MISSING_ADDRESS], $byNumber[$prefix . '-ADDRESS']);
        self::assertSame([GpsrManufacturerRule::MISSING_CONTACT], $byNumber[$prefix . '-MAIL']);
        self::assertSame([GpsrManufacturerRule::MISSING_MANUFACTURER], $byNumber[$prefix . '-NONE']);
        self::assertArrayNotHasKey($prefix . '-OK', $byNumber);
        self::assertArrayNotHasKey($prefix . '-OFF', $byNumber);

        $ok = $this->scanner()->scan($context, 1, 25, 'ok', $prefix);
        self::assertCount(1, $ok->items);
        self::assertSame($prefix . '-OK', $ok->items[0]->productNumber);
        self::assertGreaterThanOrEqual(4, $open->counts['checked']);
    }

    public function testResultIsPaged(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = $this->storefrontSalesChannelId($context);
        $factory = $this->catalog();
        $prefix = 'PDC-PAGE-' . substr(Uuid::randomHex(), 0, 8);

        foreach (['A', 'B', 'C'] as $suffix) {
            $factory->product($context, $salesChannelId, $prefix . '-' . $suffix, 'Lücke ' . $suffix, null);
        }

        $page = $this->scanner()->scan($context, 1, 2, 'open', $prefix);
        self::assertSame(3, $page->total);
        self::assertCount(2, $page->items);

        $next = $this->scanner()->scan($context, 2, 2, 'open', $prefix);
        self::assertSame(3, $next->total);
        self::assertCount(1, $next->items);
        self::assertNotSame($page->items[0]->productNumber, $next->items[0]->productNumber);
    }

    public function testCustomFieldsCanBeRecreated(): void
    {
        $installer = $this->getContainer()->get(GpsrManufacturerFieldInstaller::class);
        self::assertInstanceOf(GpsrManufacturerFieldInstaller::class, $installer);
        $context = Context::createDefaultContext();

        $installer->ensureFields($context);
        $installer->ensureFields($context);
        self::assertSame(1, $this->fieldSetCount($context));

        $installer->removeFields($context);
        self::assertSame(0, $this->fieldSetCount($context));

        $installer->ensureFields($context);
        self::assertSame(1, $this->fieldSetCount($context));
    }

    public function testVariantChildInheritsTheParentGapAndTheParentStaysOut(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = $this->storefrontSalesChannelId($context);
        $factory = $this->catalog();
        $prefix = 'PDC-VAR-' . substr(Uuid::randomHex(), 0, 8);
        $manufacturer = $factory->manufacturer($context, $prefix . ' hersteller', [
            GpsrManufacturerFields::CONTACT => 'kind@pdc-example.test',
        ]);
        $factory->product($context, $salesChannelId, $prefix . '-PARENT', 'Vater', $manufacturer);
        $parentId = $this->productId($context, $prefix . '-PARENT');
        $factory->variant($context, $parentId, $prefix . '-CHILD');

        $open = $this->scanner()->scan($context, 1, 25, 'open', $prefix);
        $byNumber = [];
        foreach ($open->items as $item) {
            $byNumber[$item->productNumber] = $item;
        }

        self::assertArrayNotHasKey($prefix . '-PARENT', $byNumber);
        self::assertArrayHasKey($prefix . '-CHILD', $byNumber);
        self::assertSame([GpsrManufacturerRule::MISSING_ADDRESS], $byNumber[$prefix . '-CHILD']->missing);
        self::assertSame($prefix . ' hersteller', $byNumber[$prefix . '-CHILD']->manufacturerName);
    }

    public function testNullChildCountStillAppears(): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = $this->storefrontSalesChannelId($context);
        $factory = $this->catalog();
        $prefix = 'PDC-NULL-' . substr(Uuid::randomHex(), 0, 8);
        $number = $prefix . '-OPEN';
        $factory->product($context, $salesChannelId, $number, 'Ohne Zähler', null);
        $productId = $this->productId($context, $number);

        $connection = $this->getContainer()->get(Connection::class);
        self::assertInstanceOf(Connection::class, $connection);
        $connection->executeStatement(
            'UPDATE product SET child_count = NULL WHERE id = :id',
            ['id' => Uuid::fromHexToBytes($productId)],
        );

        $open = $this->scanner()->scan($context, 1, 25, 'open', $prefix);
        $numbers = array_map(static fn ($item) => $item->productNumber, $open->items);
        self::assertContains($number, $numbers);
    }

    public function testAppDataRemovalClearsOnlyPluginValues(): void
    {
        $context = Context::createDefaultContext();
        $factory = $this->catalog();
        $name = 'PDC-CLEAN-' . substr(Uuid::randomHex(), 0, 8);
        $id = $factory->manufacturer($context, $name, [
            GpsrManufacturerFields::ADDRESS => CatalogFactory::address(),
            GpsrManufacturerFields::CONTACT => 'clean@pdc-example.test',
        ]);
        $this->repository('product_manufacturer.repository')->update([[
            'id' => $id,
            'customFields' => [
                GpsrManufacturerFields::ADDRESS => CatalogFactory::address(),
                GpsrManufacturerFields::CONTACT => 'clean@pdc-example.test',
                'pdc_other_field' => 'bleibt',
            ],
        ]], $context);

        $cleaner = $this->getContainer()->get(GpsrManufacturerDataCleaner::class);
        self::assertInstanceOf(GpsrManufacturerDataCleaner::class, $cleaner);
        $cleaner->removeStoredValues();

        $manufacturer = $this->repository('product_manufacturer.repository')->search(new Criteria([$id]), $context)->get($id);
        self::assertNotNull($manufacturer);
        $fields = $manufacturer->getCustomFields() ?? [];
        self::assertArrayNotHasKey(GpsrManufacturerFields::ADDRESS, $fields);
        self::assertArrayNotHasKey(GpsrManufacturerFields::CONTACT, $fields);
        self::assertSame('bleibt', $fields['pdc_other_field'] ?? null);
    }

    public function testControllerRejectsUnknownStatus(): void
    {
        $controller = $this->getContainer()->get(GpsrManufacturerController::class);
        self::assertInstanceOf(GpsrManufacturerController::class, $controller);

        $response = $controller->list(
            Request::create('/api/_action/esch-pflicht-daten-cockpit/gpsr-manufacturer', 'GET', ['status' => 'nope']),
            Context::createDefaultContext(),
        );

        self::assertSame(400, $response->getStatusCode());
    }

    private function scanner(): GpsrManufacturerScanner
    {
        $scanner = $this->getContainer()->get(GpsrManufacturerScanner::class);
        self::assertInstanceOf(GpsrManufacturerScanner::class, $scanner);

        return $scanner;
    }

    private function catalog(): CatalogFactory
    {
        return new CatalogFactory(
            $this->repository('product.repository'),
            $this->repository('product_manufacturer.repository'),
            $this->repository('tax.repository'),
        );
    }

    private function repository(string $id): EntityRepository
    {
        $repository = $this->getContainer()->get($id);
        self::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }

    private function productId(Context $context, string $number): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $number));
        $criteria->setLimit(1);
        $id = $this->repository('product.repository')->searchIds($criteria, $context)->firstId();
        self::assertIsString($id);

        return $id;
    }

    private function storefrontSalesChannelId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        $criteria->setLimit(1);
        $id = $this->repository('sales_channel.repository')->searchIds($criteria, $context)->firstId();
        self::assertIsString($id);

        return $id;
    }

    private function fieldSetCount(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', GpsrManufacturerFields::SET_NAME));

        return \count($this->repository('custom_field_set.repository')->searchIds($criteria, $context)->getIds());
    }
}
