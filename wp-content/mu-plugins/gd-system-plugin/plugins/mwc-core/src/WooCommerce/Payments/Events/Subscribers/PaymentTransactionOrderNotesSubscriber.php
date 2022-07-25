<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use GoDaddy\WordPress\MWC\Core\Payments\Adapters\PaymentTransactionOrderNoteAdapter;

/**
 * Payment transaction order notes subscriber event.
 *
 * @since 2.10.0
 */
class PaymentTransactionOrderNotesSubscriber extends TransactionOrderNotesSubscriber
{
    /** @var string overrides the transaction order notes adapter */
    protected $adapter = PaymentTransactionOrderNoteAdapter::class;
}
