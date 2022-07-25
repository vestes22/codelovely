<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Payments;

/**
 * Transaction order note adapter.
 */
class TransactionOrderNoteAdapter implements DataSourceAdapterContract
{
    /** @var AbstractTransaction source */
    protected $source;

    /**
     * TransactionOrderNoteAdapter constructor.
     * @param AbstractTransaction $transaction
     */
    public function __construct(AbstractTransaction $transaction)
    {
        $this->source = $transaction;
    }

    /**
     * Converts from Data Source format.
     *
     * @return array
     * @throws Exception
     */
    public function convertFromSource() : array
    {
        $statusMessage = $this->getStatusMessage();

        if ($transactionId = $this->source->getRemoteId()) {
            /* translators: Placeholder: %s - transaction ID */
            $statusMessage .= ' ('.sprintf(__('Transaction ID %s', 'mwc-core'), $transactionId).')';
        }

        $statusMessage = StringHelper::endWith($statusMessage, '.');

        $notes = [$statusMessage];

        $resultMessage = $this->source->getResultMessage();

        if (! $this->isTransactionApproved() && $resultMessage) {
            if ($this->source->getResultCode()) {
                /* translators: Placeholders: %1$s - approved result code, %2$s - approved result message */
                $notes[] = sprintf(__('Result: [%1$s] %2$s', 'mwc-core'),
                    $this->source->getResultCode(),
                    $resultMessage
                );
            } else {
                $notes[] = 'Result: '.$resultMessage;
            }
        }

        return $notes;
    }

    /**
     * Converts to Data Source format.
     *
     * @return AbstractTransaction
     */
    public function convertToSource() : AbstractTransaction
    {
        return $this->source;
    }

    /**
     * Gets the status message.
     *
     * @return string
     * @throws Exception
     */
    protected function getStatusMessage() : string
    {
        return trim(sprintf(
            /* translators: Placeholders: %1$s - provider name, %2$s - transaction type, provider name, %3$s - total amount, %4$s - transaction info (if applicable, or empty string) */
            __('%1$s %2$s in the amount of %3$s%4$s', 'mwc-core'),
            $this->getProviderLabel($this->source->getProviderName()),
            $this->source->getType(),
            $this->getTotalAmount(),
            strtolower($this->source->getStatus() ? ' '.$this->source->getStatus()->getLabel() : '')
        ));
    }

    /**
     * Gets the provider label for a given provider name.
     *
     * @param string|null $providerName
     * @return string
     * @throws Exception if provider not found
     */
    protected function getProviderLabel($providerName) : string
    {
        /** @var Payments $payments */
        $payments = Payments::getInstance();

        return $payments->provider($providerName ?? '')->getLabel();
    }

    /**
     * Gets the total amount converted to WooCommerce price standards.
     *
     * @return string
     */
    protected function getTotalAmount() : string
    {
        $amount = $this->source->getTotalAmount();

        $convertedAmount = (new CurrencyAmountAdapter(0, ''))
            ->convertToSource($amount);

        return wc_price($convertedAmount, $amount->getCurrencyCode());
    }

    /**
     * Determines whether the transaction in this adapter is approved.
     *
     * @return bool
     */
    protected function isTransactionApproved() : bool
    {
        return $this->source->getStatus() instanceof ApprovedTransactionStatus;
    }
}
