<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;

/**
 * WooCommerce Payment transaction datastore class.
 */
class OrderPaymentTransactionDataStore extends OrderTransactionDataStore
{
    /**
     * Saves transaction data to the data store.
     *
     * This method overrides the parent to handle virtual terminal properties.
     *
     * @param AbstractTransaction|null $transaction
     * @return AbstractTransaction
     * @throws Exception
     */
    public function save(AbstractTransaction $transaction = null) : AbstractTransaction
    {
        $transaction = parent::save($transaction);

        $order = $transaction->getOrder();
        $wcOrder = $order ? OrdersRepository::get($order->getId()) : null;

        if (! $wcOrder) {
            return $transaction;
        }

        if ($source = $transaction->getSource()) {
            $wcOrder->set_created_via((string) $source);

            if ('virtual_terminal' === $source) {
                $wcOrder->set_payment_method_title(__('Virtual Terminal', 'mwc-core'));
            }

            $wcOrder->save();
        }

        return $transaction;
    }
}
