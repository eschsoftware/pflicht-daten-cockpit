<?php declare(strict_types=1);

namespace EschSoftware\PflichtDatenCockpit\Core\Rule;

final readonly class RuleFinding
{
    public const STATUS_OPEN = 'open';

    public const STATUS_OK = 'ok';

    /**
     * @param list<string> $missing
     */
    public function __construct(
        public array $missing,
        public string $status,
    ) {
    }

    public function blocksCheckout(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
