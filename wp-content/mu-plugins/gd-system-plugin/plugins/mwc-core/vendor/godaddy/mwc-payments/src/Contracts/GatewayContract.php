<?php

namespace GoDaddy\WordPress\MWC\Payments\Contracts;

use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Http\Response;

/**
 * Gateway contract.
 *
 * @since 0.1.0
 */
interface GatewayContract
{
    /**
     * Performs a request.
     *
     * @since 0.1.0
     *
     * @param string $method HTTP method
     * @param array $data request data
     * @return Response
     */
    public function doRequest(string $method, array $data): Response;

    /**
     * Gets a request object.
     *
     * @since 0.1.0
     *
     * @return Request
     */
    public function getRequest(): Request;
}
