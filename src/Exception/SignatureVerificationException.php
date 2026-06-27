<?php

declare(strict_types=1);

namespace PayHub\Exception;

/** A webhook signature could not be verified (bad signature, stale, malformed). */
final class SignatureVerificationException extends \RuntimeException implements PayHubException
{
}
