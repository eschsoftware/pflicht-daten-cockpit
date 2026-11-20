<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Checkout;

use Shopware\Core\Checkout\Cart\Error\Error;

final class GpsrManufacturerBlockedError extends Error
{
    public function __construct(
        protected string $productId,
        protected string $name,
        protected string $number,
    ) {
        parent::__construct(sprintf(
            'GPSR manufacturer data missing for "%s" (%s)',
            $name,
            $number,
        ));
    }

    public function getName(): string
    {
        $label = trim($this->name);

        if ($label === '') {
            return $this->number;
        }

        return $label . ' (' . $this->number . ')';
    }

    public function getId(): string
    {
        return $this->getMessageKey() . '-' . $this->productId;
    }

    public function getMessageKey(): string
    {
        return 'esch-pdc-gpsr-manufacturer-blocked';
    }

    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }

    public function isPersistent(): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        return [
            'name' => $this->getName(),
            'number' => $this->number,
        ];
    }
}
