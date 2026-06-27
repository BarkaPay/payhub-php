<?php

declare(strict_types=1);

namespace PayHub\Exception;

/** The SDK was constructed or called with invalid arguments. */
final class ConfigurationException extends \InvalidArgumentException implements PayHubException
{
}
