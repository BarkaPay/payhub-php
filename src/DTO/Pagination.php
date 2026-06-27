<?php

declare(strict_types=1);

namespace PayHub\DTO;

use PayHub\Util\Coerce;

final class Pagination
{
    public function __construct(
        public readonly ?int $currentPage,
        public readonly ?int $lastPage,
        public readonly ?int $perPage,
        public readonly ?int $total,
    ) {
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function fromArray(array $meta): self
    {
        return new self(
            currentPage: Coerce::int($meta, 'current_page'),
            lastPage: Coerce::int($meta, 'last_page'),
            perPage: Coerce::int($meta, 'per_page'),
            total: Coerce::int($meta, 'total'),
        );
    }
}
