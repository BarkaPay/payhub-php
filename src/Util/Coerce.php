<?php

declare(strict_types=1);

namespace PayHub\Util;

/**
 * Defensive readers for decoded JSON — keep DTO hydration null-safe and tidy.
 *
 * @internal
 */
final class Coerce
{
    /**
     * @param array<string, mixed> $data
     */
    public static function str(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return \is_int($value) ? $value : (\is_string($value) && ctype_digit($value) ? (int) $value : null);
    }

    /**
     * Amount as returned by the API (whole number for fiat, decimal string for
     * crypto). Returned as-is to avoid lossy coercion.
     *
     * @param array<string, mixed> $data
     */
    public static function amount(array $data, string $key): int|float|string|null
    {
        $value = $data[$key] ?? null;

        return \is_int($value) || \is_float($value) || \is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|null
     */
    public static function arr(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        return \is_array($value) ? $value : null;
    }
}
