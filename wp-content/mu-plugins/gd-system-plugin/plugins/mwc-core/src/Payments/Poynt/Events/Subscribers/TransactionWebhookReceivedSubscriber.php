<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\RefundsRepository;
use GoDaddy\WordPress\MWC\Core\Events\BeforeCreateRefundEvent;
use GoDaddy\WordPress\MWC\Core\Events\BeforeCreateVoidEvent;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderCaptureTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderPaymentTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\WebhookReceivedEvent;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\AuthorizationTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\CaptureTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\PaymentTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\RefundTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\VoidTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\GetTransactionRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Request;
use GoDaddy\WordPress\MWC\Payments\Events\CaptureTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Events\PaymentTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Events\VoidTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AuthorizationTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\CaptureTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\VoidTransaction;
use InvalidArgumentException;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Order_Refund;

/**
 * An events' subscriber for transaction-specific webhooks.
 */
class TransactionWebhookReceivedSubscriber implements SubscriberContract
{
    /** @var string transaction captured webhook event type */
    const TRANSACTION_CAPTURED_EVENT_TYPE = 'TRANSACTION_CAPTURED';

    /** @var string transaction authorized webhook event type */
    const TRANSACTION_AUTHORIZED_EVENT_TYPE = 'TRANSACTION_AUTHORIZED';

    /** @var string transaction refunded webhook event type */
    const TRANSACTION_REFUNDED_EVENT_TYPE = 'TRANSACTION_REFUNDED';

    /** @var string transaction voided webhook event type */
    const TRANSACTION_VOIDED_EVENT_TYPE = 'TRANSACTION_VOIDED';

    /** @var string transaction authorize action */
    const TRANSACTION_AUTHORIZE_ACTION = 'AUTHORIZE';

    /** @var string transaction capture action */
    const TRANSACTION_CAPTURE_ACTION = 'CAPTURE';

    /** @var string transaction sale action */
    const TRANSACTION_SALE_ACTION = 'SALE';

    /**
     * Handles the transaction received event.
     *
     * @param EventContract $event
     * @return void
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        // only handle Poynt webhook received events
        if (! is_a($event, WebhookReceivedEvent::class)) {
            return;
        }

        $this->handleEventPayload($event->getPayloadDecoded());
    }

    /**
     * Handles the given event payload.
     *
     * @param array $payload
     * @throws Exception
     */
    protected function handleEventPayload(array $payload)
    {
        switch (ArrayHelper::get($payload, 'eventType', '')) {
            case static::TRANSACTION_AUTHORIZED_EVENT_TYPE:
                $transaction = $this->getAdaptedPaymentTransaction($payload);
                /* @see TransactionWebhookReceivedSubscriber::handleTransactionAuthorizedEvent() */
                $handlerMethod = 'handleTransactionAuthorizedEvent';
                break;
            case static::TRANSACTION_CAPTURED_EVENT_TYPE:
                $transaction = $this->getAdaptedTransactionFromCapturedEvent($payload);
                $handlerMethod = $transaction instanceof CaptureTransaction
                    /* @see TransactionWebhookReceivedSubscriber::handleTransactionCapturedEvent() */
                    ? 'handleTransactionCapturedEvent'
                    /* @see TransactionWebhookReceivedSubscriber::handleTransactionSaleEvent() */
                    : 'handleTransactionSaleEvent';
                break;
            case static::TRANSACTION_REFUNDED_EVENT_TYPE:
                $transaction = $this->getAdaptedRefundTransaction($payload);
                /* @see TransactionWebhookReceivedSubscriber::handleTransactionRefundedEvent() */
                $handlerMethod = 'handleTransactionRefundedEvent';
                break;
            case static::TRANSACTION_VOIDED_EVENT_TYPE:
                $transaction = $this->getAdaptedVoidTransaction($payload);
                /* @see TransactionWebhookReceivedSubscriber::handleTransactionVoidedEvent() */
                $handlerMethod = 'handleTransactionVoidedEvent';
                break;
            default:
                return;
        }

        if (! $transaction instanceof AbstractTransaction) {
            return;
        }

        $transaction->setSource('remote');

        // if the transaction doesn't have a provider name set it from the order's data store
        if (! $transaction->getProviderName() && $order = $transaction->getOrder()) {
            $transaction->setProviderName(OrderTransactionDataStore::readProviderName($order->getId()));
        }

        $this->$handlerMethod($transaction);
    }

    /**
     * Gets a new remote transaction request for a given ID.
     *
     * @see TransactionWebhookReceivedSubscriber::getRemoteTransactionResponse()
     *
     * @param string $transactionId
     * @return Request
     * @throws Exception
     */
    protected function getRemoteTransactionRequest(string $transactionId) : Request
    {
        return new GetTransactionRequest($transactionId);
    }

    /**
     * Gets the remote transaction from Poynt API.
     *
     * @see TransactionWebhookReceivedSubscriber::fetchRemoteTransaction()
     *
     * @param string $transactionId
     * @return Response
     * @throws Exception
     */
    protected function getRemoteTransactionResponse(string $transactionId) : Response
    {
        $response = $this->getRemoteTransactionRequest($transactionId)->send();

        if ($response->isError() || 200 !== $response->getStatus()) {
            throw new Exception(sprintf(
                'Could not retrieve transaction %1$s (%2$s).',
                $transactionId,
                $response->getErrorMessage() ?: $response->getStatus()
            ));
        }

        return $response;
    }

    /**
     * Gets an adapted transaction for the given remote ID.
     *
     * @param Response $response
     * @param string $transactionClass
     * @return array|PaymentTransaction|AuthorizationTransaction|CaptureTransaction|RefundTransaction|VoidTransaction
     * @throws Exception
     */
    protected function getAdaptedTransaction(Response $response, string $transactionClass)
    {
        return $this->getTransactionAdapter($transactionClass)
            ->convertToSource($response);
    }

    /**
     * Gets a transaction adapter instance for a given transaction class.
     *
     * @param string $transactionClass
     * @return PaymentTransactionAdapter|AuthorizationTransactionAdapter|CaptureTransactionAdapter|RefundTransactionAdapter|VoidTransactionAdapter
     * @throws InvalidArgumentException
     */
    protected function getTransactionAdapter(string $transactionClass) : DataSourceAdapterContract
    {
        switch ($transactionClass) {
            case PaymentTransaction::class:
                return new PaymentTransactionAdapter(new PaymentTransaction());
            case AuthorizationTransaction::class:
                return new AuthorizationTransactionAdapter(new AuthorizationTransaction());
            case CaptureTransaction::class:
                return new CaptureTransactionAdapter(new CaptureTransaction());
            case RefundTransaction::class:
                return new RefundTransactionAdapter(new RefundTransaction());
            case VoidTransaction::class:
                return new VoidTransactionAdapter(new VoidTransaction());
            default:
                throw new InvalidArgumentException(sprintf('Invalid transaction class %s to get a transaction adapter for.', $transactionClass));
        }
    }

    /**
     * Gets the transaction order.
     *
     * @param string $remoteId
     * @param string $type
     * @return Order|null
     * @throws Exception
     */
    protected function getTransactionOrder(string $remoteId, string $type)
    {
        // bail if the required meta values are missing
        if (! $remoteId || ! $type) {
            return null;
        }

        $filter = Register::filter()
            ->setGroup('woocommerce_order_data_store_cpt_get_orders_query')
            ->setHandler([$this, 'filterOrdersByTransactionRemoteId'])
            ->setArgumentsCount(2);

        $filter->execute();

        $foundOrders = OrdersRepository::query([
            'limit' => 1,
            'mwc'   => [
                'transaction' => [
                    'providerName' => 'poynt',
                    'remoteId'     => $remoteId,
                    'type'         => $type,
                ],
            ],
            'type' => 'shop_order',
        ]);

        $filter->deregister();

        if (! empty($foundOrders)) {
            $transactionOrder = current($foundOrders);

            if ($transactionOrder instanceof WC_Order) {
                return $this->getAdaptedTransactionOrder($transactionOrder);
            }
        }

        return null;
    }

    /**
     * Filters the orders query by transaction remote ID.
     *
     * @internal callback for the woocommerce_order_data_store_cpt_get_orders_query filter
     *
     * @see TransactionWebhookReceivedSubscriber::getTransactionOrder()
     * @see OrdersRepository::query()
     *
     * @param mixed $queryVars
     * @param mixed $customVars
     * @return array|mixed may be filtered by third parties
     */
    public function filterOrdersByTransactionRemoteId($queryVars, $customVars)
    {
        $providerName = ArrayHelper::get($customVars, 'mwc.transaction.providerName');
        $remoteId = ArrayHelper::get($customVars, 'mwc.transaction.remoteId');
        $type = ArrayHelper::get($customVars, 'mwc.transaction.type');

        if (is_string($providerName) && is_string($type)) {
            // account for existing meta query key in the query arguments
            if (! ArrayHelper::exists($queryVars, 'meta_query') || ! ArrayHelper::accessible($queryVars['meta_query'])) {
                $queryVars['meta_query'] = [];
            } elseif (! ArrayHelper::exists($queryVars['meta_query'], 'relation')) {
                $queryVars['meta_query']['relation'] = 'AND';
            }

            $queryVars['meta_query'][] = [
                'key'     => sprintf('_%1$s_%2$s_remoteId', $providerName, $type),
                'value'   => $remoteId,
                'compare' => '=',
            ];
        }

        return $queryVars;
    }

    /**
     * Gets an adapted transaction order.
     *
     * @param WC_Order $transactionOrder
     * @return Order
     * @throws Exception
     */
    protected function getAdaptedTransactionOrder(WC_Order $transactionOrder) : Order
    {
        return (new OrderAdapter($transactionOrder))->convertFromSource();
    }

    /**
     * Gets an adapted transaction object from the given webhook payload.
     *
     * Poynt can send the CAPTURED event for both real captures and SALE transactions.
     *
     * @param array $payload
     * @return PaymentTransaction|CaptureTransaction|null
     * @throws Exception
     */
    protected function getAdaptedTransactionFromCapturedEvent(array $payload)
    {
        $response = $this->getRemoteTransactionResponse(ArrayHelper::get($payload, 'resourceId', ''));

        switch (ArrayHelper::get($response->getBody() ?? [], 'action', '')) {
            case static::TRANSACTION_AUTHORIZE_ACTION:
                return $this->getAdaptedCaptureTransactionFromAuthorization($response);
            case static::TRANSACTION_CAPTURE_ACTION:
                return $this->getAdaptedCaptureTransactionFromCapture($response);
            case static::TRANSACTION_SALE_ACTION:
                return $this->getAdaptedPaymentTransaction($payload);
            default:
                return null;
        }
    }

    /**
     * Gets a capture transaction adapted from the given authorization transaction response.
     *
     * @param Response $response
     * @return CaptureTransaction|null
     * @throws Exception
     */
    protected function getAdaptedCaptureTransactionFromAuthorization(Response $response)
    {
        /** @var AuthorizationTransaction $authTransaction */
        $authTransaction = $this->getAdaptedTransaction($response, AuthorizationTransaction::class);

        $response = $this->getRemoteTransactionResponse($authTransaction->getRemoteCaptureId());

        /** @var CaptureTransaction $transaction */
        $transaction = $this->getAdaptedTransaction($response, CaptureTransaction::class);
        $transaction->setRemoteId($authTransaction->getRemoteCaptureId());

        if (! $order = $this->getTransactionOrder($authTransaction->getRemoteId() ?? '', 'payment')) {
            return null;
        }

        return $transaction->setOrder($order)
            ->setProviderName('poynt');
    }

    /**
     * Gets a capture transaction adapted from the given capture transaction response.
     *
     * @param Response $response
     * @return CaptureTransaction|null
     * @throws Exception
     */
    protected function getAdaptedCaptureTransactionFromCapture(Response $response)
    {
        /** @var CaptureTransaction $transaction */
        $transaction = $this->getAdaptedTransaction($response, CaptureTransaction::class);

        if (! $order = $this->getTransactionOrder($transaction->getRemoteId() ?? '', 'payment')) {
            return null;
        }

        return $transaction->setOrder($order)
            ->setProviderName('poynt');
    }

    /**
     * Handles a transaction capture event.
     *
     * @param CaptureTransaction $transaction
     * @throws Exception
     */
    protected function handleTransactionCapturedEvent(CaptureTransaction $transaction)
    {
        /** @var Order $order */
        $order = $transaction->getOrder();

        // bail if the transaction capture event has already been processed
        if ($order->isCaptured()) {
            return;
        }

        $wcOrder = OrdersRepository::get($order->getId());

        if (! $wcOrder) {
            return;
        }

        $this->addOrderItemFees($transaction, $wcOrder);

        $this->getOrderCaptureTransactionDataStoreForProvider($transaction->getProviderName())->save($transaction);

        Events::broadcast(new CaptureTransactionEvent($transaction));

        if ('on-hold' === $wcOrder->get_status()) {
            $wcOrder->update_status('processing');
        }
    }

    /**
     * Gets the order capture transaction data store for a given provider.
     *
     * @param string $providerName
     * @return OrderCaptureTransactionDataStore
     */
    protected function getOrderCaptureTransactionDataStoreForProvider(string $providerName) : OrderCaptureTransactionDataStore
    {
        return new OrderCaptureTransactionDataStore($providerName);
    }

    /**
     * Handles a transaction sale (capture) event.
     *
     * @param PaymentTransaction $transaction
     * @throws Exception
     */
    protected function handleTransactionSaleEvent(PaymentTransaction $transaction)
    {
        /** @var Order $order */
        $order = $transaction->getOrder();

        // bail if sale event (capture) has already been processed
        if ($order->isCaptured()) {
            return;
        }

        $wcOrder = OrdersRepository::get($order->getId());

        if (! $wcOrder) {
            return;
        }

        $this->addOrderItemFees($transaction, $wcOrder);

        $this->getOrderPaymentTransactionDataStoreForProvider($transaction->getProviderName())
            ->save($transaction);

        $wcOrder->payment_complete($transaction->getRemoteId());

        Events::broadcast(new PaymentTransactionEvent($transaction));
    }

    /**
     * Handles a transaction authorization event.
     *
     * @param PaymentTransaction $transaction
     * @throws Exception
     */
    protected function handleTransactionAuthorizedEvent(PaymentTransaction $transaction)
    {
        $orderId = $transaction->getOrder()->getId();
        $paymentTransaction = $this->getOrderPaymentTransactionDataStoreForProvider('poynt')
            ->read($orderId, 'payment');

        // bail if authorization event has already been processed
        if ($paymentTransaction->getRemoteId()) {
            return;
        }

        $wcOrder = OrdersRepository::get($orderId);

        if (! $wcOrder) {
            return;
        }

        $this->addOrderItemFees($transaction, $wcOrder);

        $this->getOrderPaymentTransactionDataStoreForProvider($transaction->getProviderName())
            ->save($transaction);

        if ('on-hold' === $wcOrder->get_status()) {
            $wcOrder->update_status('processing');
        }

        Events::broadcast(new PaymentTransactionEvent($transaction));
    }

    /**
     * Gets a payment transaction adapted from the given webhook payload.
     *
     * @param array $payload
     *
     * @return PaymentTransaction|null
     * @throws Exception
     */
    protected function getAdaptedPaymentTransaction(array $payload)
    {
        $response = $this->getRemoteTransactionResponse(ArrayHelper::get($payload, 'resourceId', ''));

        /** @var PaymentTransaction $transaction */
        $transaction = $this->getAdaptedTransaction($response, PaymentTransaction::class);

        $remoteOrderReference = current(ArrayHelper::where(ArrayHelper::get($response->getBody() ?? [], 'references', []), function ($value) {
            return 'POYNT_ORDER' === ArrayHelper::get($value, 'type');
        }));

        if (! $order = $this->getTransactionOrder(ArrayHelper::get($remoteOrderReference, 'id', ''), 'order')) {
            return null;
        }

        return $transaction->setOrder($order)
            ->setProviderName('poynt');
    }

    /**
     * Gets the order payment transaction data store for a given provider.
     *
     * @param string $providerName
     * @return OrderPaymentTransactionDataStore
     */
    protected function getOrderPaymentTransactionDataStoreForProvider(string $providerName) : OrderPaymentTransactionDataStore
    {
        return new OrderPaymentTransactionDataStore($providerName);
    }

    /**
     * Handles a transaction refund event.
     *
     * @param RefundTransaction $transaction
     * @throws Exception
     */
    protected function handleTransactionRefundedEvent(RefundTransaction $transaction)
    {
        // bail early if the order is not attached
        if (null === $transaction->getOrder()) {
            return;
        }

        $wcOrder = OrdersRepository::get($transaction->getOrder()->getId());

        // bail if the order could not be found
        if (null === $wcOrder) {
            return;
        }

        // remote refund has already been processed: skip
        if ($wcOrder->get_meta('_poynt_refund_remoteId')) {
            return;
        }

        $this->maybeFilterWooCommerceOrderPaymentMethod($transaction, $wcOrder);

        Events::broadcast(new BeforeCreateRefundEvent($transaction));

        // perform a partial or full refund, re-stocking any items
        $refund = RefundsRepository::create($this->getRefundArgs($transaction, $wcOrder));

        $refund->update_meta_data('_poynt_refund_remoteId', $transaction->getRemoteId());
        $refund->save();

        $wcOrder->update_meta_data('_mwc_payments_status_before_refund', $wcOrder->get_status());
        $wcOrder->update_meta_data('_poynt_refund_remoteId', $transaction->getRemoteId());
        $wcOrder->save();
    }

    /**
     * Handles a transaction void event.
     *
     * @param VoidTransaction $transaction
     * @throws Exception
     */
    protected function handleTransactionVoidedEvent(VoidTransaction $transaction)
    {
        $order = $transaction->getOrder();
        $wcOrder = OrdersRepository::get($order->getId());

        if (! $wcOrder) {
            return;
        }

        switch ($transaction->getParentType()) {
            case 'capture':
                $this->handleCaptureVoided($transaction, $wcOrder);
                break;
            case 'payment':
                $this->handlePaymentVoided($transaction, $wcOrder);
                break;
            case 'refund':
                $this->handleRefundVoided($transaction, $wcOrder);
                break;
        }
    }

    /**
     * Gets a void transaction adapted from the given webhook payload.
     *
     * @param array $payload
     * @return VoidTransaction
     * @throws Exception
     */
    protected function getAdaptedVoidTransaction(array $payload)
    {
        $response = $this->getRemoteTransactionResponse(ArrayHelper::get($payload, 'properties.childTxnId', ''));

        /** @var VoidTransaction $transaction */
        $transaction = $this->getAdaptedTransaction($response, VoidTransaction::class);

        if (! $order = $this->getTransactionOrder($transaction->getRemoteParentId() ?? '', $transaction->getParentType() ?? '')) {
            return null;
        }

        return $transaction->setOrder($order);
    }

    /**
     * Gets a refund transaction adapted from the given webhook payload.
     *
     * @param array $payload
     * @return RefundTransaction
     * @throws Exception
     */
    protected function getAdaptedRefundTransaction(array $payload)
    {
        $response = $this->getRemoteTransactionResponse(ArrayHelper::get($payload, 'resourceId', ''));

        /** @var RefundTransaction $transaction */
        $transaction = $this->getAdaptedTransaction($response, RefundTransaction::class);

        if (! $order = $this->getTransactionOrder($transaction->getRemoteParentId() ?? '', 'payment')) {
            return null;
        }

        return $transaction->setOrder($order);
    }

    /**
     * Voids a capture for a given order.
     *
     * @param VoidTransaction $transaction
     * @param WC_Order $wcOrder
     */
    protected function handleCaptureVoided(VoidTransaction $transaction, WC_Order $wcOrder)
    {
        // don't replay capture void if already voided
        if (! $transaction->getOrder()->isCaptured()) {
            return;
        }

        $wcOrder->update_meta_data('_mwc_payments_is_captured', 'no');
        $wcOrder->set_status('on-hold');
        $wcOrder->save();

        Events::broadcast(new VoidTransactionEvent($transaction));
    }

    /**
     * Handles a void transaction to void a payment for a given WooCommerce order.
     *
     * @param VoidTransaction $transaction
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function handlePaymentVoided(VoidTransaction $transaction, WC_Order $wcOrder)
    {
        // bail if remote void has already been processed
        if ($wcOrder->get_meta('_poynt_void_remoteId')) {
            return;
        }

        $this->voidWooCommerceOrder($transaction, $wcOrder);
    }

    /**
     * Voids a WooCommerce order.
     *
     * This method handles the business creating a "refund" record and firing events as if the void was performed in WooCommerce.
     *
     * @param VoidTransaction $transaction
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function voidWooCommerceOrder(VoidTransaction $transaction, WC_Order $wcOrder)
    {
        $this->maybeFilterWooCommerceOrderPaymentMethod($transaction, $wcOrder);

        Events::broadcast(new BeforeCreateVoidEvent($transaction));

        // perform a full refund, re-stocking all items
        RefundsRepository::create($this->getRefundArgs($transaction, $wcOrder));

        $wcOrder->update_meta_data('_poynt_void_remoteId', $transaction->getRemoteId());
        $wcOrder->save();
    }

    /**
     * Voids (deletes) a refund for a given void refund transaction.
     *
     * @param VoidTransaction $transaction
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function handleRefundVoided(VoidTransaction $transaction, WC_Order $wcOrder)
    {
        if (! $refund = $this->findTransactionRefund($transaction)) {
            return;
        }

        $refund->delete();

        Events::broadcast(new VoidTransactionEvent($transaction));

        if ($previousStatus = $wcOrder->get_meta('_mwc_payments_status_before_refund')) {
            $wcOrder->update_status($previousStatus);
        }
    }

    /**
     * Finds a WooCommerce refund associated with the given void transaction.
     *
     * @param VoidTransaction $transaction
     * @return WC_Order_Refund|null
     * @throws Exception
     */
    protected function findTransactionRefund(VoidTransaction $transaction)
    {
        $filter = Register::filter()
            ->setGroup('woocommerce_order_data_store_cpt_get_orders_query')
            ->setHandler([$this, 'filterOrdersByTransactionRemoteId'])
            ->setArgumentsCount(2);

        $filter->execute();

        $foundRefunds = RefundsRepository::query([
            'limit' => 1,
            'mwc'   => [
                'transaction' => [
                    'providerName' => 'poynt',
                    'remoteId'     => $transaction->getRemoteParentId(),
                    'type'         => 'refund',
                ],
            ],
        ]);

        $filter->deregister();

        if (empty($foundRefunds)) {
            return null;
        }

        $refund = current($foundRefunds);

        return $refund instanceof WC_Order_Refund ? $refund : null;
    }

    /**
     * Adds WooCommerce order item fees based on transaction data.
     *
     * @param AbstractTransaction $transaction
     * @param WC_Order $wcOrder
     */
    protected function addOrderItemFees(AbstractTransaction $transaction, WC_Order $wcOrder)
    {
        $shouldCalculateTotals = false;

        // may add a tip amount, if any
        if (is_callable([$transaction, 'getTipAmount']) && ! empty($tipAmount = $transaction->getTipAmount())) {
            $shouldCalculateTotals = $this->addOrderItemFee(__('Tip', 'mwc-core'), $tipAmount, $wcOrder);
        }

        // may add a cashback amount, if any
        if (is_callable([$transaction, 'getCashbackAmount']) && ! empty($cashbackAmount = $transaction->getCashbackAmount())) {
            $shouldCalculateTotals = $this->addOrderItemFee(__('Cashback', 'mwc-core'), $cashbackAmount, $wcOrder) || $shouldCalculateTotals;
        }

        if ($shouldCalculateTotals) {
            $wcOrder->calculate_totals();
        }
    }

    /**
     * Adds an order item fee to an order.
     *
     * This method can be used to add items from a transaction like a tip or a cashback.
     *
     * @param string $itemFeeName
     * @param CurrencyAmount $amount
     * @param WC_Order $order
     * @return bool
     */
    protected function addOrderItemFee(string $itemFeeName, CurrencyAmount $amount, WC_Order $order) : bool
    {
        if (0 === $amount->getAmount() || $this->orderHasItemFee($order, $itemFeeName)) {
            return false;
        }

        $convertedAmount = (new CurrencyAmountAdapter(0, ''))->convertToSource($amount);

        $item = $this->createOrderItemFee($convertedAmount, $itemFeeName);

        $order->add_item($item);
        $order->add_order_note(sprintf(
            /* translators: Placeholders: %1$s - item fee name, %2$s - item fee amount */
            __('%1$s amount of %2$s added to order by GoDaddy Payments Smart Terminal', 'mwc-core'),
            $itemFeeName,
            wc_price($convertedAmount, get_woocommerce_currency_symbol())
        ));

        return true;
    }

    /**
     * Creates a new WooCommerce order item fee with the provided amount.
     *
     * All fees created here are non-taxable and must be used to add order items like tip, cashback, etc.
     *
     * @TODO consider moving this method to a MWC Common repository method while implementing MWC-3115 {unfulvio 2021-11-02}
     *
     * @param float $amount
     * @param string $feeName
     * @return WC_Order_Item_Fee
     */
    protected function createOrderItemFee(float $amount, string $feeName) : WC_Order_Item_Fee
    {
        $item = new WC_Order_Item_Fee();
        $item->set_name($feeName);
        $item->set_amount($amount);
        $item->set_total($amount);
        $item->set_tax_status('none');

        return $item;
    }

    /**
     * Determines whether an order has an item fee with a given name.
     *
     * @TODO consider moving this method to a MWC Common repository method while implementing MWC-3115 {unfulvio 2021-11-02}
     *
     * @param WC_Order $order
     * @param string $itemFeeName
     * @return bool
     */
    protected function orderHasItemFee(WC_Order $order, string $itemFeeName) : bool
    {
        foreach ($order->get_fees() as $item) {
            if ($item instanceof WC_Order_Item_Fee && $itemFeeName === $item->get_name()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Maybe filters the WooCommerce order payment method.
     *
     * Tells WooCommerce that we want to process a refund or a void with the transaction provider gateway,
     * regardless of the actual gateway used (e.g. Bank Transfer, Cash on Delivery...).
     *
     * @see WC_Order::get_payment_method() corresponding filter hook
     *
     * @param AbstractTransaction $transaction
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function maybeFilterWooCommerceOrderPaymentMethod(AbstractTransaction $transaction, WC_Order $wcOrder)
    {
        if ($transaction->getProviderName() !== $wcOrder->get_payment_method()) {
            Register::filter()
                ->setGroup('woocommerce_order_get_payment_method')
                ->setHandler([$transaction, 'getProviderName'])
                ->execute();
        }
    }

    /**
     * Prepares refund arguments given a refund or a void transaction.
     *
     * @param RefundTransaction|VoidTransaction $transaction
     * @param WC_Order $wcOrder
     * @return array list of prepared arguments to generate a WooCommerce order refund
     * @throws Exception
     */
    protected function getRefundArgs(RefundTransaction $transaction, WC_Order $wcOrder) : array
    {
        // for voids, refund the full amount
        if ($transaction instanceof VoidTransaction) {
            $amount = $wcOrder->get_total();
        } else {
            $amount = (new CurrencyAmountAdapter(0, ''))->convertToSource($transaction->getTotalAmount());
        }

        $args = [
            'amount'          => $amount,
            'reason'          => $this->getRefundDescription($transaction),
            'order_id'        => $wcOrder->get_id(),
            'refund_payment'  => true,
            'restock_items'   => true,
            'skip_bopit_sync' => true,
        ];

        // for voids and full refunds we can tell WooCommerce to mark each line item as refunded
        if ($transaction instanceof VoidTransaction || $transaction->getOrder()->getTotalAmount()->getAmount() === $transaction->getTotalAmount()->getAmount()) {
            $args['line_items'] = $this->parseLineItemsForRefund($wcOrder->get_items(['line_item', 'fee', 'shipping']));
        }

        return $args;
    }

    /**
     * Converts WooCommerce order item objects for refund handling.
     *
     * Formats the items as used by {@see wc_create_refund()}.
     * @see RefundsRepository::create()
     *
     * @param WC_Order_Item[]|WC_Order_Item_Product[]|WC_Order_Item_Fee[]|WC_Order_Item_Shipping[] $lineItems
     * @return array
     */
    protected function parseLineItemsForRefund(array $lineItems) : array
    {
        $result = [];

        foreach ($lineItems as $id => $item) {
            if (! $item instanceof WC_Order_Item) {
                continue;
            }

            // should we be using totals or subtotals here?
            $result[$id] = [
                'qty'          => $item->get_type() === 'line_item' ? $item->get_quantity() : 0,
                'refund_total' => $item->get_total(),
                'refund_tax'   => ArrayHelper::get($item->get_taxes(), 'total'),
            ];
        }

        return $result;
    }

    /**
     * Gets the refund description to be appended to an order item.
     *
     * @param RefundTransaction|VoidTransaction $transaction
     * @return string
     */
    protected function getRefundDescription(RefundTransaction $transaction) : string
    {
        if (is_a($transaction, VoidTransaction::class) || $transaction->getOrder()->getTotalAmount()->getAmount() === $transaction->getTotalAmount()->getAmount()) {
            return __('From GoDaddy Payments Hub. Order fully refunded.', 'mwc-core');
        }

        return __('From GoDaddy Payments Hub. Order partially refunded.', 'mwc-core');
    }
}
