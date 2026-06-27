<?php

declare(strict_types=1);

namespace PayHub\Exception;

/** The HTTP request never completed (DNS, connection, timeout). No response. */
final class NetworkException extends \RuntimeException implements PayHubException
{
}
