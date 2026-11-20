<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core\Rule;

final readonly class ProductCheckInput
{
    public function __construct(
        public bool $manufacturerAssigned,
        public ?string $manufacturerName,
        public ?string $postalAddress,
        public ?string $contactEmail,
    ) {
    }
}
