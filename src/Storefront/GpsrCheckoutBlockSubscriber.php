<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Storefront;

use EschSoftware\PflichtDatenCockpit\Checkout\GpsrManufacturerBlockedError;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Shopware leert die Cart-Fehler, bevor das Offcanvas gerendert wird, und zeigt sie als Flash.
 * Das Flag bleibt an der Page, damit „Zur Kasse“ trotzdem deaktiviert werden kann.
 */
final class GpsrCheckoutBlockSubscriber implements EventSubscriberInterface
{
    public const EXTENSION = 'eschPdcGpsrBlocked';

    public static function getSubscribedEvents(): array
    {
        return [
            OffcanvasCartPageLoadedEvent::class => 'flagBlockedCheckout',
        ];
    }

    public function flagBlockedCheckout(OffcanvasCartPageLoadedEvent $event): void
    {
        foreach ($event->getPage()->getCart()->getErrors() as $error) {
            if (!$error instanceof GpsrManufacturerBlockedError) {
                continue;
            }

            $event->getPage()->addExtension(self::EXTENSION, new ArrayStruct(['blocked' => true]));

            return;
        }
    }
}
