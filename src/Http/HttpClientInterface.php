<?php

declare(strict_types=1);

namespace PayHub\Http;

use PayHub\Exception\NetworkException;

/**
 * Minimal transport contract. The SDK ships a zero-dependency curl
 * implementation ({@see CurlClient}); pass your own to integrate an existing
 * HTTP stack (e.g. a PSR-18 adapter) or to fake the network in tests.
 */
interface HttpClientInterface
{
    /**
     * @throws NetworkException when the request never reaches a response
     */
    public function send(Request $request): Response;
}
