<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;

/**
 * Void transaction order note adapter.
 */
class VoidTransactionOrderNoteAdapter extends TransactionOrderNoteAdapter
{
    /**
     * Gets the status message.
     *
     * @return string
     * @throws Exception
     */
    protected function getStatusMessage() : string
    {
        // change the message depending on the void's parent transaction type
        switch ($this->source->getParentType()) {
            case 'capture':
                $message = sprintf(
                    /* translators: Placeholders: %s - the total amount of the capture that was voided */
                    __('A payment capture of %s was successfully voided', 'mwc-core'),
                    $this->getTotalAmount()
                );
                break;
            case 'refund':
                $message = sprintf(
                    /* translators: Placeholders: %s - the total amount of the refund that was voided */
                    __('A refund of %s was successfully voided', 'mwc-core'),
                    $this->getTotalAmount()
                );
                break;
            default:
                $message = parent::getStatusMessage();
        }

        // add the payments hub tag if this was a remotely generated transaction
        if ('remote' === $this->source->getSource()) {
            $message .= ' via GoDaddy Payments Hub';
        }

        return StringHelper::endWith($message, '.');
    }
}
