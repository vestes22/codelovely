<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\CancelRemotePoyntOrderException;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\CompleteRemotePoyntOrderException;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\RefundRemotePoyntOrderException;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderRefundTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\RefundTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CancelOrderRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CompleteOrderRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\ForceCompleteOrderRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\PutTransactionRequest;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;
use WC_Order;

/**
 * GoDaddy Payments order synchronization to Poynt API.
 */
class OrderSynchronization
{
    /**
     * Order synchronization constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Adds action and filter hooks.
     *
     * @throws Exception
     * @return void
     */
    protected function addHooks()
    {
        // TODO: instead of doing the handleOrderStatusCompleted work here in this class, move that logic to a Sync class, as we do with PushOrdersProducer.php {JS - 2021-10-17}
        Register::action()
                ->setGroup('woocommerce_order_status_completed')
                ->setHandler([$this, 'handleOrderStatusCompleted'])
                ->setArgumentsCount(1)
                ->execute();

        Register::action()
                ->setGroup('woocommerce_order_status_cancelled')
                ->setHandler([$this, 'handleOrderStatusCancelled'])
                ->setArgumentsCount(1)
                ->execute();

        // Note rather than the woocommerce_order_status_refunded action we're using
        // woocommerce_create_refund & woocommerce_refund_created which provides the refund details
        Register::action()
                ->setGroup('woocommerce_create_refund')
                ->setHandler([$this, 'handleCreateRefund'])
                ->setArgumentsCount(2)
                ->execute();

        Register::action()
                ->setGroup('woocommerce_refund_created')
                ->setHandler([$this, 'handleRefundCreated'])
                ->setArgumentsCount(2)
                ->execute();
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function handleOrderStatusCompleted($orderId)
    {
        $this->handleOrderStatusChange('Completed', (int) $orderId);
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function handleOrderStatusCancelled($orderId)
    {
        $this->handleOrderStatusChange('Cancelled', (int) $orderId);
    }

    /**
     * This handles the case of doing a manual refund/void (one where the payment is
     * not automatically reversed).
     *
     * @param int $orderId
     * @param array $args arguments passed to wc_create_refund
     * @return void
     */
    public function handleRefundCreated($refundId, $args)
    {
        if (ArrayHelper::get($args, 'skip_bopit_sync')) {
            return;
        }

        if (! ($wcRefundOrder = OrdersRepository::get($refundId))) {
            return;
        }

        $this->handleOrderStatusChange('Refunded', (int) $wcRefundOrder->get_parent_id(), $args);
    }

    /**
     * This handles the case of doing a "GoDaddy Payments - Pay in Person" refund/void
     * on an order that was placed online with GDP Pay in Person and paid on the terminal.
     *
     * @param WC_Order $wcRefundOrder
     * @param array $args arguments passed to wc_create_refund
     * @return void
     */
    public function handleCreateRefund($wcRefundOrder, $args)
    {
        if (ArrayHelper::get($args, 'skip_bopit_sync')) {
            return;
        }

        if (! $wcOrder = OrdersRepository::get($wcRefundOrder->get_parent_id())) {
            return;
        }

        $order = (new OrderAdapter($wcOrder))->convertFromSource();

        if (! $this->shouldPushOrderDetailsToPoynt($order)) {
            return;
        }

        $orderTransaction = (new OrderTransactionDataStore('poynt'))->read($order->getId(), 'payment');

        if (! $orderTransaction->getRemoteId()) {
            return;
        }

        if (empty($transactionProviderName = $wcOrder->get_meta('_mwc_transaction_provider_name', true))) {
            return;
        }

        // tell WC that we want to process this refund with the poynt gateway
        Register::filter()
            ->setGroup('woocommerce_order_get_payment_method')
            ->setHandler(function () use ($transactionProviderName) {
                return $transactionProviderName;
            })
            ->execute();
    }

    /**
     * @param string $status
     * @param int $orderId
     * @param array $additionalArgs additional arguments to pass along
     * @return void
     */
    public function handleOrderStatusChange(string $status, int $orderId, array $additionalArgs = [])
    {
        if (! ($wcOrder = OrdersRepository::get($orderId))) {
            return;
        }

        $order = (new OrderAdapter($wcOrder))->convertFromSource();

        if (! $this->shouldPushOrderDetailsToPoynt($order)) {
            return;
        }

        try {
            $methodName = 'doHandleOrderStatus'.$status;
            $this->$methodName($wcOrder, $order, $additionalArgs);
        } catch (Exception $exception) {
            // @TODO: Do nothing for now -- add uncatchable method to SentryRepository or to BaseException so Woo can't intercept these {JO: 2021-10-18}
        }
    }

    /**
     * @param WC_Order $wcOrder
     * @param Order $order
     * @param array $notUsed
     * @return void
     * @throws Exception
     */
    protected function doHandleOrderStatusCompleted(WC_Order $wcOrder, Order $order, array $notUsed = [])
    {
        $response = (new CompleteOrderRequest($order->getRemoteId()))->send();

        if ($response->getStatus() === 400 && ArrayHelper::get($response->getBody(), 'code') === 'ITEM_NOT_FULFILLED_OR_RETURNED') {
            $response = (new ForceCompleteOrderRequest($order->getRemoteId()))->send();

            if ($response->isError() || $response->getStatus() !== 200) {
                $errorMessage = ArrayHelper::get($response->getBody(), 'developerMessage');
                throw new CompleteRemotePoyntOrderException("Could not forceComplete Poynt order {$order->getRemoteId()}: ({$response->getStatus()}) {$errorMessage}");
            }
        } elseif ($response->isError() || $response->getStatus() !== 200) {
            $errorMessage = ArrayHelper::get($response->getBody(), 'developerMessage');
            throw new CompleteRemotePoyntOrderException("Could not complete Poynt order {$order->getRemoteId()}: ({$response->getStatus()}) {$errorMessage}");
        }
    }

    /**
     * @param WC_Order $wcOrder
     * @param int Order $order
     * @param array $notUsed
     * @return void
     * @throws Exception
     */
    protected function doHandleOrderStatusCancelled(WC_Order $wcOrder, Order $order, array $notUsed = [])
    {
        $response = (new CancelOrderRequest($order->getRemoteId()))->send();

        if ($response->isError() || $response->getStatus() !== 200) {
            $errorMessage = ArrayHelper::get($response->getBody(), 'developerMessage');
            throw new CancelRemotePoyntOrderException("Could not cancel Poynt order {$order->getRemoteId()}: ({$response->getStatus()}) {$errorMessage}");
        }
    }

    /**
     * @param WC_Order $wcOrder
     * @param Order $order
     * @param array $args the arguments passed to wc_create_refund
     * @return void
     * @throws Exception
     */
    protected function doHandleOrderStatusRefunded(WC_Order $wcOrder, Order $order, array $args = [])
    {
        if ($wcOrder->get_meta('_poynt_refund_remoteId')) {
            // this refund has already been pushed to Poynt
            return;
        }

        $orderTransaction = (new OrderTransactionDataStore())->read($order->getId(), 'payment');

        if ($orderTransaction->getProviderName() == 'poynt' && ! $order->isCaptured() && $orderTransaction->getRemoteId()) {
            // pass off authorization voids to the Poynt payment gateway for
            // actual processing since the Poynt API does not allow for a
            // "manual" void transaction to be created
            return CorePaymentGateways::getManagedPaymentGatewayInstance('poynt')->process_refund(
                $order->getId(),
                ArrayHelper::get($args, 'amount'),
                ArrayHelper::get($args, 'reason')
            );
        }

        $this->performManualRefund($order, $orderTransaction, $args);
    }

    /**
     * Create a "manual" refund transaction in Poynt for the sole purpose of
     * marking the order as refunded; no actual funds will be refunded or
     * voided.
     *
     * A manual refund can be:
     *
     * - A WC "manual" refund of a GDP paid order (has $args['refund_total'] == false)
     * - A WC "manual" refund of a 3rd party paid order (has $args['refund_total'] == false)
     * - A WC 3rd party refund of a 3rd party paid order (has $args['refund_total'] == true)
     *
     * @param Order $order the order to refund
     * @param AbstractTransaction $orderTransaction
     * @param array $args the arguments passed to wc_create_refund
     * @return void
     * @throws Exception
     */
    protected function performManualRefund(Order $order, AbstractTransaction $orderTransaction, array $args = [])
    {
        $remoteRefundTransactionId = StringHelper::generateUuid4();
        $wcOrder = OrdersRepository::get($order->getId());
        $fundingSourceProvider = ArrayHelper::get($args, 'refund_payment') ? $orderTransaction->getProviderName() : 'manual';

        $response = (new PutTransactionRequest($remoteRefundTransactionId))
            ->body($this->buildRefundTransactionRequestBody($order, $wcOrder, $fundingSourceProvider, $remoteRefundTransactionId, $args))
            ->send();

        if ($response->isError() || $response->getStatus() !== 201) {
            $errorMessage = ArrayHelper::get($response->getBody(), 'developerMessage');
            throw new RefundRemotePoyntOrderException("Could not cancel Poynt order {$order->getRemoteId()}: ({$response->getStatus()}) {$errorMessage}");
        }

        if ($orderTransaction->getProviderName() == 'poynt') {
            // if the provider of this transaction is poynt, persist the full set of meta data
            (new OrderRefundTransactionDataStore())
                ->save(
                    (new RefundTransactionAdapter(new RefundTransaction()))
                        ->convertToSource($response)
                        ->setOrder($order)
                );
        } else {
            // otherwise for a 3rd party transaction provider just record the remote poynt refund id
            // something to be aware of: we're assuming this update meta is persisted *before* the Poynt transaction webhook generated by the refund request on the lines above is received and processed
            $wcOrder->update_meta_data('_poynt_refund_remoteId', ArrayHelper::get($response->getBody(), 'id', ''));
        }

        // TODO: add the ability to set a fundingSource.provider attribute on the RefundTransaction object so that it can be persisted in the line above rather than here {JS - 2021-10-17}
        $wcOrder->update_meta_data('_poynt_refund_fundingSource_provider', $fundingSourceProvider);
        $wcOrder->save();
    }

    /**
     * @param Order $order order to build the refund transaction request body for
     * @param WC_Order $wcOrder
     * @param string $fundingSourceProvider the refund transaction funding source provider name
     * @param string $remoteRefundTransactionId the remote refund transaction ID to set
     * @param array $args the arguments passed to wc_create_refund
     * @return array the refund transaction request body
     */
    protected function buildRefundTransactionRequestBody(Order $order, WC_Order $wcOrder, string $fundingSourceProvider, string $remoteRefundTransactionId, array $args)
    {
        // TODO: pretty sure that the wc order meta data model that we're using won't support multiple partial refunds so we'll have revisit that {JS - 2021-10-17}
        $orderTotal = $order->getTotalAmount();
        $refundTotal = (new CurrencyAmountAdapter(ArrayHelper::get($args, 'amount'), $orderTotal->getCurrencyCode()))->convertFromSource();

        if (! $refundTotal) {
            throw new RefundRemotePoyntOrderException("Unable to get refund total to refund {$order->getId()}");
        }

        if (ArrayHelper::get($args, 'refund_payment')) {
            /* translators: %1$s: payment gateway name */
            $refundNotes = sprintf(__('Transaction refunded by %1$s from WooCommerce.', 'mwc-core'), $wcOrder->get_payment_method_title());
        } else {
            $refundNotes = __('Transaction manually refunded from WooCommerce.', 'mwc-core');
        }

        if ($reason = ArrayHelper::get($args, 'reason')) {
            $refundNotes .= "\n\n{$reason}";
        }

        $body = [
            'action'   => 'REFUND',
            'amounts' => [
                'currency'          => $refundTotal->getCurrencyCode(),
                'orderAmount'       => $orderTotal->getAmount(),
                'transactionAmount' => $refundTotal->getAmount(),
            ],
            'fundingSource' => [
                'type' => 'CUSTOM_FUNDING_SOURCE',
                'customFundingSource' => [
                    'type'      => 'OTHER',
                    'provider'  => $fundingSourceProvider,
                    'accountId' => 'none',
                    'processor' => 'co.poynt.services',
                ],
            ],
            'processorResponse' => [
                'status'        => 'Successful',
                'statusCode'    => 1,
                'transactionId' => $remoteRefundTransactionId,
            ],
            'references' => [
                [
                    'type' => 'POYNT_ORDER',
                    'id'   => $order->getRemoteId(),
                ],
            ],
            'context'  => [
                'sourceApp'  => Configuration::get('payments.poynt.api.source', ''),
                'businessId' => Configuration::get('payments.poynt.businessId', ''),
                'storeId'    => Configuration::get('payments.poynt.storeId', ''),
            ],
            'notes' => $refundNotes,
        ];

        // read the poynt capture transaction (if any) from meta
        $captureTransaction = $this->getPoyntTransaction($order, 'capture');

        if ($remoteCaptureTransactionId = $captureTransaction->getRemoteId()) {
            $body['parentId'] = $remoteCaptureTransactionId;
        } else {
            // read the poynt payment transaction (if any) from meta
            $paymentTransaction = $this->getPoyntTransaction($order, 'payment');

            if ($remotePaymentTransactionId = $paymentTransaction->getRemoteId()) {
                $body['parentId'] = $remotePaymentTransactionId;
            }
        }

        return $body;
    }

    /**
     * Read the Poynt transaction (if any) from meta.
     *
     * @param \GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order $order
     * @param string $type capture or payment
     * @return AbstractTransaction
     */
    protected function getPoyntTransaction(Order $order, string $type)
    {
        return (new OrderTransactionDataStore('poynt'))->read($order->getId(), $type);
    }

    /**
     * @param Order $order
     * @return bool true if this order should be synchronized to the Poynt API
     */
    protected function shouldPushOrderDetailsToPoynt(Order $order)
    {
        if (! Poynt::shouldPushOrderDetailsToPoynt($order)) {
            return false;
        }

        return (bool) $order->getRemoteId();
    }
}
