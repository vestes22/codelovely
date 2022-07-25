<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to get a remote Poynt Order.
 */
class GetOrderRequest extends AbstractResourceRequest
{
    /** @var string */
    const RESOURCE_PLURAL = 'orders';

    /**
     * GetOrderRequest constructor.
     *
     * @param string $orderId
     * @throws Exception
     */
    public function __construct(string $orderId)
    {
        $this->setMethod('GET');
        parent::__construct(static::RESOURCE_PLURAL, $orderId);
    }
}
