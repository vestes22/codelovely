<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Apple Pay request.
 */
class ApplePayRequest extends AbstractBusinessRequest
{
    /**
     * Apple Pay request constructor.
     *
     * @param string $endpoint sets the request endpoint to the base route
     * @throws Exception
     */
    public function __construct(string $endpoint = '')
    {
        $endpoint = trim($endpoint);

        $this->route = $endpoint ? "apple-pay/{$endpoint}" : 'apple-pay';

        parent::__construct();
    }
}
