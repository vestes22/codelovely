<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Capture transaction API request.
 *
 * @since 2.10.0
 */
class CaptureTransactionRequest extends AbstractTransactionRequest
{
    /**
     * CaptureTransactionRequest constructor.
     *
     * @param string|null $transactionId
     *
     * @throws Exception
     */
    public function __construct(string $transactionId = null)
    {
        $this->setMethod('POST');
        $this->route = 'capture';

        parent::__construct($transactionId);
    }
}
