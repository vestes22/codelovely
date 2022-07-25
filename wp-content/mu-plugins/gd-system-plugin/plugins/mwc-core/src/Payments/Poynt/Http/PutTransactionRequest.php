<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to create a Poynt 3rd party transaction (one not fulfilled by GoDaddy Payments).
 */
class PutTransactionRequest extends AbstractResourceRequest
{
    /** @var string */
    const RESOURCE_PLURAL = 'transactions';

    /**
     * @param string $remoteTransactionId identifies the remote transaction to create
     * @throws Exception
     */
    public function __construct(string $remoteTransactionId)
    {
        $this->setMethod('PUT');

        parent::__construct(static::RESOURCE_PLURAL, $remoteTransactionId);
    }
}
