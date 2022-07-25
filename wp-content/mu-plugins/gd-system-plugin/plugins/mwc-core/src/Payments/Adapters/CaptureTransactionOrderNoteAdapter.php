<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use Exception;

/**
 * Capture transaction order note adapter.
 */
class CaptureTransactionOrderNoteAdapter extends TransactionOrderNoteAdapter
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
                /* translators: Placeholder: %s - captured amount  */
                __('A payment of %s was successfully captured via GoDaddy Payments Hub', 'mwc-core'),
                $this->getTotalAmount()
            );
    }
}
