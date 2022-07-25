<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;

/**
 * Payment transaction order note adapter.
 *
 * @property PaymentTransaction $source
 */
class PaymentTransactionOrderNoteAdapter extends TransactionOrderNoteAdapter
{
    /**
     * Converts the source transaction into order notes.
     *
     * Overridden to add payment method info to the notes.
     *
     * @return array
     * @throws Exception
     */
    public function convertFromSource() : array
    {
        $notes = parent::convertFromSource();

        $paymentMethod = $this->source->getPaymentMethod();
        if ($paymentMethod && $paymentMethod instanceof CardPaymentMethod) {
            $notes[0] .= sprintf(
                ' %1$s ending in %2$s (expires %3$s/%4$s)',
                $paymentMethod->getBrand() ? $paymentMethod->getBrand()->getLabel() : 'Card',
                $paymentMethod->getLastFour(),
                $paymentMethod->getExpirationMonth(),
                substr($paymentMethod->getExpirationYear(), -2)
            );
        }

        if ($this->source->isAuthOnly()) {
            $notes[0] .= ' (Authorization only transaction)';
        }

        return $notes;
    }
}
