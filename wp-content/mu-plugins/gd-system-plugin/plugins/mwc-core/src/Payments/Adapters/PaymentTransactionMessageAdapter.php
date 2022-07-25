<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;

/**
 * Payment transaction message adapter.
 */
class PaymentTransactionMessageAdapter extends TransactionMessageAdapter
{
    /**
     * Converts status of transaction to a status message.
     *
     * @since 2.10.0
     *
     * @return string
     */
    public function convertFromSource() : string
    {
        $status = $this->source->getStatus();

        if ($status instanceof ApprovedTransactionStatus && ! $this->source->isAuthOnly()) {
            return 'Thank you. Your order has been received.';
        }

        if ($status instanceof DeclinedTransactionStatus) {
            if (Configuration::get('payments.'.$this->source->getProviderName().'.detailedDecline')) {
                return __('We cannot process your order with the payment information that you provided. Please use a different payment account or an alternate payment method.', 'mwc-core');
            } else {
                return __('An error occurred, please try again or try an alternate form of payment.', 'mwc-core');
            }
        }

        return 'Your order has been received and is being reviewed. Thank you for your business.';
    }
}
