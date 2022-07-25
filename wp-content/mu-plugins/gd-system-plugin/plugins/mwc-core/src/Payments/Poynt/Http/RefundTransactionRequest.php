<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

/**
 * Refund transaction API request.
 *
 * @since 2.10.0
 */
class RefundTransactionRequest extends AbstractTransactionRequest
{
    /** @var string request method */
    public $method = 'POST';
}
