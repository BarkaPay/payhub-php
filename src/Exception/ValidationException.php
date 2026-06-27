<?php

declare(strict_types=1);

namespace PayHub\Exception;

/**
 * 422 — request validation failed. `getErrors()` returns the field → messages
 * map from the API.
 */
final class ValidationException extends ApiException
{
}
