<?php

declare(strict_types=1);

namespace PayHub\Exception;

/** 409 — duplicate / already-exists (e.g. the 60s duplicate-transfer guard). */
final class ConflictException extends ApiException
{
}
