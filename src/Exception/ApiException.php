<?php

declare(strict_types=1);

namespace PayHub\Exception;

/**
 * Base class for every error returned by the PayHub API (any response with the
 * standard error envelope or a non-2xx status). Subclasses map the most common
 * cases so you can catch them precisely.
 */
class ApiException extends \RuntimeException implements PayHubException
{
    /**
     * @param array<string, mixed> $errors field-level details (validation) or context
     */
    public function __construct(
        string $message,
        private readonly string $errorCode = '',
        private readonly int $httpStatus = 0,
        private readonly ?string $requestId = null,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /** Machine-readable code from the envelope (e.g. `validation_error`). */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** Correlation id echoed by the API — quote it to support. */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /** @return array<string, mixed> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Build the most specific exception for an error envelope.
     *
     * @param array<string, mixed> $envelope
     */
    public static function fromEnvelope(int $httpStatus, array $envelope): self
    {
        $code = \is_string($envelope['code'] ?? null) ? $envelope['code'] : '';
        $rawMessage = $envelope['message'] ?? null;
        $message = \is_string($rawMessage) && $rawMessage !== ''
            ? $rawMessage
            : 'PayHub API error (HTTP ' . $httpStatus . ').';
        $requestId = \is_string($envelope['request_id'] ?? null) ? $envelope['request_id'] : null;
        /** @var array<string, mixed> $errors */
        $errors = \is_array($envelope['errors'] ?? null) ? $envelope['errors'] : [];

        $class = self::classFor($code, $httpStatus);

        return new $class($message, $code, $httpStatus, $requestId, $errors);
    }

    /** @return class-string<self> */
    private static function classFor(string $code, int $httpStatus): string
    {
        return match ($code) {
            'unauthenticated', 'invalid_api_key', 'incorrect_credentials' => AuthenticationException::class,
            'unauthorized', 'ip_not_allowed', 'merchant_suspended', 'merchant_closed' => AuthorizationException::class,
            'validation_error', 'invalid_input' => ValidationException::class,
            'resource_not_found' => NotFoundException::class,
            'duplicate_transaction', 'resource_already_exists' => ConflictException::class,
            'too_many_requests' => RateLimitException::class,
            'service_unavailable', 'operator_unavailable' => ServiceUnavailableException::class,
            default => match (true) {
                401 === $httpStatus => AuthenticationException::class,
                403 === $httpStatus => AuthorizationException::class,
                404 === $httpStatus => NotFoundException::class,
                409 === $httpStatus => ConflictException::class,
                422 === $httpStatus => ValidationException::class,
                429 === $httpStatus => RateLimitException::class,
                503 === $httpStatus => ServiceUnavailableException::class,
                $httpStatus >= 500 => ServerException::class,
                default => self::class,
            },
        };
    }
}
