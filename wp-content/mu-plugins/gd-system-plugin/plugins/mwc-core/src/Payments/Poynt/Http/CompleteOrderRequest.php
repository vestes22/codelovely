<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to complete a remote Poynt Order.
 */
class CompleteOrderRequest extends AbstractResourceRequest
{
    /** @var string */
    const RESOURCE_PLURAL = 'orders';

    /** @var string the API action */
    const RESOURCE_ACTION = 'complete';

    /**
     * @param string $remoteOrderId identifies the remote order to complete
     * @throws Exception
     */
    public function __construct(string $remoteOrderId)
    {
        $this->setMethod('POST');
        $this->route = static::RESOURCE_ACTION;

        parent::__construct(static::RESOURCE_PLURAL, $remoteOrderId);
    }
}
