<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Transactions;

/**
 * Void transaction.
 *
 * @since 0.1.0
 */
class VoidTransaction extends RefundTransaction
{
    /** @var string type */
    protected $type = 'void';
}
