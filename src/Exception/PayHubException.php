<?php

declare(strict_types=1);

namespace PayHub\Exception;

/**
 * Marker interface implemented by every exception the SDK throws, so callers
 * can `catch (\PayHub\Exception\PayHubException $e)` to trap anything from the
 * SDK in one place.
 */
interface PayHubException extends \Throwable
{
}
