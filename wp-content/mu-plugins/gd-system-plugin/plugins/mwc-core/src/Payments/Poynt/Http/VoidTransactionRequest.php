<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Void transaction API request.
 *
 * @since 2.10.0
 */
class VoidTransactionRequest extends AbstractTransactionRequest
{
    /**
     * VoidTransactionRequest constructor.
     *
     * @param string|null $transactionId
     *
     * @throws Exception
     */
    public function __construct(string $transactionId = null)
    {
        $this->setMethod('POST');
        $this->route = 'void';

        parent::__construct($transactionId);
    }
}
