<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to force complete a remote Poynt Order. This can be used when a
 * standard complete request fails due to the order not technically being
 * in a state that allows for completion, e.g. due to having items that are
 * neither fulfilled, nor returned.
 */
class ForceCompleteOrderRequest extends AbstractResourceRequest
{
    /** @var string */
    const RESOURCE_PLURAL = 'orders';

    /** @var string the API action */
    const RESOURCE_ACTION = 'forceComplete';

    /**
     * @param string $remoteOrderId identifies the remote order to force complete
     * @throws Exception
     */
    public function __construct(string $remoteOrderId)
    {
        $this->setMethod('POST');
        $this->route = static::RESOURCE_ACTION;

        parent::__construct(static::RESOURCE_PLURAL, $remoteOrderId);
    }
}
