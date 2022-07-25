<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use Exception;

/**
 * Refund transaction order note adapter.
 */
class RefundTransactionOrderNoteAdapter extends TransactionOrderNoteAdapter
{
    /**
     * Gets the status message.
     *
     * @return string
     * @throws Exception
     */
    protected function getStatusMessage(): string
    {
        return 'remote' !== $this->source->getSource()
            ? parent::getStatusMessage()
            : sprintf(
                /* translators: Placeholder: %s - refunded amount */
                __('A refund of %s was successfully processed via GoDaddy Payments Hub', 'mwc-core'),
                $this->getTotalAmount()
            );
    }
}
