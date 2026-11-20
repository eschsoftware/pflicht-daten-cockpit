<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core;

use EschSoftware\PflichtDatenCockpit\Core\Rule\GpsrManufacturerRule;

final readonly class ScanPage
{
    /**
     * @param array{checked: int, open: int, ok: int} $counts
     * @param list<CatalogGap> $items
     */
    public function __construct(
        public array $counts,
        public array $items,
        public int $page,
        public int $limit,
        public int $total,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rule' => GpsrManufacturerRule::CODE,
            'counts' => $this->counts,
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'items' => array_map(
                static fn (CatalogGap $gap): array => $gap->toArray(),
                $this->items,
            ),
        ];
    }
}
