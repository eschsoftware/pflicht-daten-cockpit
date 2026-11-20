<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Test\Integration;

use EschSoftware\PflichtDatenCockpit\Checkout\GpsrManufacturerBlockedError;
use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerFields;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Order\OrderPersister;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final class GpsrCheckoutBlockTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testMissingAddressBlocksTheOrder(): void
    {
        $salesChannelContext = $this->salesChannelContext();
        $context = $salesChannelContext->getContext();
        $factory = $this->catalog();
        $manufacturerId = $factory->manufacturer($context, 'PDC Checkout ohne Adresse', [
            GpsrManufacturerFields::CONTACT => 'checkout@pdc-example.test',
        ]);
        $productId = $factory->product(
            $context,
            $salesChannelContext->getSalesChannelId(),
            'PDC-CHECKOUT-GAP-' . substr(Uuid::randomHex(), 0, 6),
            'Checkout-Lücke',
            $manufacturerId,
        );

        $cart = $this->calculate($salesChannelContext, $productId);

        $blocked = false;
        foreach ($cart->getErrors() as $error) {
            if ($error instanceof GpsrManufacturerBlockedError) {
                $blocked = true;
                self::assertTrue($error->blockOrder());
                self::assertStringContainsString('Checkout-Lücke', $error->getName());
            }
        }

        self::assertTrue($blocked, 'Expected a GPSR block on the cart.');
        self::assertTrue($cart->getErrors()->blockOrder());

        $this->expectException(InvalidCartException::class);
        $this->persister()->persist($cart, $salesChannelContext);
    }

    public function testCompleteManufacturerIsNotBlockedByThisRule(): void
    {
        $salesChannelContext = $this->salesChannelContext();
        $context = $salesChannelContext->getContext();
        $factory = $this->catalog();
        $manufacturerId = $factory->manufacturer($context, 'PDC Checkout vollständig', [
            GpsrManufacturerFields::ADDRESS => CatalogFactory::address(),
            GpsrManufacturerFields::CONTACT => 'komplett@pdc-example.test',
        ]);
        $productId = $factory->product(
            $context,
            $salesChannelContext->getSalesChannelId(),
            'PDC-CHECKOUT-OK-' . substr(Uuid::randomHex(), 0, 6),
            'Checkout vollständig',
            $manufacturerId,
        );

        $cart = $this->calculate($salesChannelContext, $productId);

        foreach ($cart->getErrors() as $error) {
            self::assertNotInstanceOf(GpsrManufacturerBlockedError::class, $error);
        }

        try {
            $this->persister()->persist($cart, $salesChannelContext);
            self::fail('A guest cart must not become an order.');
        } catch (InvalidCartException $exception) {
            self::fail('The complete product was rejected as an invalid cart: ' . $exception->getMessage());
        } catch (CartException $exception) {
            self::assertNotSame(CartException::CART_INVALID_CODE, $exception->getErrorCode());
        }
    }

    private function calculate(SalesChannelContext $context, string $productId): Cart
    {
        $cart = new Cart(Uuid::randomHex());
        $lineItem = new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, 1);
        $lineItem->setStackable(true);
        $lineItem->setRemovable(true);
        $cart->add($lineItem);

        $calculator = $this->getContainer()->get(CartCalculator::class);
        self::assertInstanceOf(CartCalculator::class, $calculator);

        return $calculator->calculate($cart, $context);
    }

    private function salesChannelContext(): SalesChannelContext
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT));
        $criteria->setLimit(1);
        $salesChannelId = $this->repository('sales_channel.repository')->searchIds($criteria, $context)->firstId();
        self::assertIsString($salesChannelId);

        $factory = $this->getContainer()->get(SalesChannelContextFactory::class);

        return $factory->create(Uuid::randomHex(), $salesChannelId);
    }

    private function persister(): OrderPersister
    {
        $persister = $this->getContainer()->get(OrderPersister::class);
        self::assertInstanceOf(OrderPersister::class, $persister);

        return $persister;
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
}
