<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;

/**
 * Transaction message adapter.
 */
class TransactionMessageAdapter implements DataSourceAdapterContract
{
    /** @var AbstractTransaction source */
    protected $source;

    /**
     * TransactionMessageAdapter constructor.
     *
     * @param AbstractTransaction $transaction
     */
    public function __construct(AbstractTransaction $transaction)
    {
        $this->source = $transaction;
    }

    /**
     * Converts from Data Source format.
     *
     * @since 2.10.0
     *
     * @return string
     */
    public function convertFromSource() : string
    {
        return '';
    }

    /**
     * Converts to Data Source format.
     *
     * @since 2.10.0
     *
     * @return AbstractTransaction
     */
    public function convertToSource() : AbstractTransaction
    {
        return $this->source;
    }
}
