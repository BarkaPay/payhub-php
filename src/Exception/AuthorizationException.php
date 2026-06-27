<?php

declare(strict_types=1);

namespace PayHub\Exception;

/** 403 / 410 / 451 — authenticated, but not allowed (scope, IP, account state). */
final class AuthorizationException extends ApiException
{
}
