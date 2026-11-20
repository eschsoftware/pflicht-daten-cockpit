<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core;

final readonly class CatalogGap
{
    /**
     * @param list<string> $missing
     */
    public function __construct(
        public string $productId,
        public string $productNumber,
        public string $productName,
        public ?string $manufacturerName,
        public array $missing,
        public string $status,
    ) {
    }

    /**
     * @return array{
     *     productId: string,
     *     productNumber: string,
     *     productName: string,
     *     manufacturerName: ?string,
     *     missing: list<string>,
     *     status: string
     * }
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'productNumber' => $this->productNumber,
            'productName' => $this->productName,
            'manufacturerName' => $this->manufacturerName,
            'missing' => $this->missing,
            'status' => $this->status,
        ];
    }
}
