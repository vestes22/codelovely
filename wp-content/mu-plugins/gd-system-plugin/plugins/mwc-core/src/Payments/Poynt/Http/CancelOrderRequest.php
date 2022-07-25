<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to cancel a remote Poynt Order.
 */
class CancelOrderRequest extends AbstractResourceRequest
{
    /** @var string */
    const RESOURCE_PLURAL = 'orders';

    /** @var string the API action */
    const RESOURCE_ACTION = 'cancel';

    /**
     * @param string $remoteOrderId identifies the remote order to cancel
     * @throws Exception
     */
    public function __construct(string $remoteOrderId)
    {
        $this->setMethod('POST');
        $this->route = static::RESOURCE_ACTION;

        parent::__construct(static::RESOURCE_PLURAL, $remoteOrderId);
    }
}
