<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core\Rule;

final class GpsrManufacturerRule
{
    public const CODE = 'gpsr-manufacturer';

    public const MISSING_MANUFACTURER = 'manufacturer';

    public const MISSING_NAME = 'name';

    public const MISSING_ADDRESS = 'address';

    public const MISSING_CONTACT = 'contact';

    public function evaluate(ProductCheckInput $input): RuleFinding
    {
        if (!$input->manufacturerAssigned) {
            return new RuleFinding([self::MISSING_MANUFACTURER], RuleFinding::STATUS_OPEN);
        }

        $missing = [];

        if (!$this->hasText($input->manufacturerName)) {
            $missing[] = self::MISSING_NAME;
        }

        if (!$this->hasText($input->postalAddress)) {
            $missing[] = self::MISSING_ADDRESS;
        }

        if (!$this->hasEmail($input->contactEmail)) {
            $missing[] = self::MISSING_CONTACT;
        }

        return new RuleFinding(
            $missing,
            $missing === [] ? RuleFinding::STATUS_OK : RuleFinding::STATUS_OPEN,
        );
    }

    private function hasText(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }

    private function hasEmail(?string $value): bool
    {
        if (!$this->hasText($value)) {
            return false;
        }

        return filter_var(trim((string) $value), \FILTER_VALIDATE_EMAIL) !== false;
    }
}
