<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Checkout;

use EschSoftware\PflichtDatenCockpit\Core\GpsrManufacturerScanner;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

final class GpsrManufacturerCartValidator implements CartValidatorInterface
{
    public function __construct(private readonly GpsrManufacturerScanner $scanner)
    {
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        $ids = [];

        foreach ($cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE) as $lineItem) {
            $id = $lineItem->getReferencedId();

            if (\is_string($id) && $id !== '') {
                $ids[] = $id;
            }
        }

        if ($ids === []) {
            return;
        }

        foreach ($this->scanner->inspectIds($ids, $context->getContext()) as $gap) {
            if (!$gap->blocksCheckout()) {
                continue;
            }

            $errors->add(new GpsrManufacturerBlockedError(
                $gap->productId,
                $gap->productName,
                $gap->productNumber,
            ));
        }
    }
}
