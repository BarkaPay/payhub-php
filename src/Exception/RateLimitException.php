<?php

declare(strict_types=1);

namespace PayHub\Exception;

/** 429 — rate limit reached. Safe to retry with backoff. */
final class RateLimitException extends ApiException
{
}
