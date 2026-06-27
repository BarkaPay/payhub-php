<?php

declare(strict_types=1);

namespace PayHub\Util;

/**
 * @internal
 */
final class Json
{
    public static function encode(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * Decode a JSON object into an associative array. Returns null for empty
     * input or anything that isn't a JSON object.
     *
     * @return array<string, mixed>|null
     */
    public static function decode(string $json): ?array
    {
        if (trim($json) === '') {
            return null;
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }
}
