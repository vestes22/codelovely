<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Request to get a remote Poynt Transaction.
 */
class GetTransactionRequest extends AbstractResourceRequest
{
    /** @var string */
    const RESOURCE_PLURAL = 'transactions';

    /**
     * GetTransactionRequest constructor.
     *
     * @param string $transactionId
     * @throws Exception
     */
    public function __construct(string $transactionId)
    {
        $this->setMethod('GET');
        parent::__construct(static::RESOURCE_PLURAL, $transactionId);
    }
}
