<?php

declare(strict_types=1);

namespace PayHub\DTO;

use PayHub\Util\Coerce;

/**
 * @implements \IteratorAggregate<int, Transfer>
 */
final class TransferCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param list<Transfer> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?Pagination $meta,
    ) {
    }

    /**
     * @param array<string, mixed> $envelope
     */
    public static function fromEnvelope(array $envelope): self
    {
        $data = Coerce::arr($envelope, 'data') ?? [];
        $rows = Coerce::arr($data, 'transfers') ?? [];

        $items = [];
        foreach ($rows as $row) {
            if (\is_array($row)) {
                /** @var array<string, mixed> $row */
                $items[] = Transfer::fromArray($row);
            }
        }

        $meta = Coerce::arr($envelope, 'meta');

        return new self($items, $meta !== null ? Pagination::fromArray($meta) : null);
    }

    /**
     * @return \ArrayIterator<int, Transfer>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }
}
