<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PaymentsProviderSettingsException;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\PaymentTransactionMessageAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\CustomerDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderCaptureTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderPaymentTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderRefundTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderVoidTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\PaymentMethodDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations\Contracts\IntegrationContract;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\CaptureTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\HeldTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\VoidTransaction;
use GoDaddy\WordPress\MWC\Payments\Payments;
use GoDaddy\WordPress\MWC\Payments\Providers\AbstractProvider;
use WC_Customer;
use WC_Order;
use WC_Payment_Gateway;
use WP_Error;

/**
 * Abstract payment gateway.
 */
abstract class AbstractPaymentGateway extends WC_Payment_Gateway
{
    /** Debug mode log to file */
    const DEBUG_MODE_LOG = 'log';

    /** Debug mode display on checkout */
    const DEBUG_MODE_CHECKOUT = 'checkout';

    /** Debug mode log to file and display on checkout */
    const DEBUG_MODE_BOTH = 'both';

    /** Debug mode disabled */
    const DEBUG_MODE_OFF = 'off';

    /** @var array bindings. */
    protected $bindings = [];

    /** @var IntegrationContract[] integrations. */
    protected $integrations = [];

    /** @var string provider name. */
    protected $providerName;

    /**
     * Constructs the Abstract Payment Gateway.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->buildSupports();
        $this->addHooks();
    }

    /**
     * Adds action and filter hooks.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_thankyou_order_received_text')
            ->setHandler([$this, 'maybeSetOrderReceivedText'])
            ->setArgumentsCount(2)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_gateway_title')
            ->setHandler([$this, 'filterGatewayTitleBySource'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Add a payment method.
     *
     * @since 2.10.0
     *
     * @return array
     */
    public function add_payment_method(): array
    {
        try {
            $this->createPaymentMethod($this->getPaymentMethodForAdd());

            return ['result' => 'success'];
        } catch (Exception $ex) {
            return ['result' => 'failure'];
        }
    }

    /**
     * Builds the supports property.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    protected function buildSupports()
    {
        $provider = $this->getProvider();
        $this->supports = $this->supports ?: [];

        if (method_exists($provider, 'transactions')) {
            if (method_exists($provider->transactions(), 'refund')) {
                $this->supports[] = 'refunds';
            }

            if (method_exists($provider->transactions(), 'void')) {
                $this->supports[] = 'voids';
            }
        }

        if (method_exists($provider, 'customers')) {
            $this->supports[] = 'customers';

            if (method_exists($provider->customers(), 'create')) {
                $this->supports[] = 'customers.create';
            }

            if (method_exists($provider->customers(), 'update')) {
                $this->supports[] = 'customers.update';
            }

            if (method_exists($provider->customers(), 'delete')) {
                $this->supports[] = 'customers.delete';
            }
        }

        if (method_exists($provider, 'paymentMethods')) {
            $paymentMethodsEnabled = Configuration::get("payments.{$this->providerName}.paymentMethods");

            if ($paymentMethodsEnabled) {
                $this->supports[] = 'tokenization';
            }

            if (method_exists($provider->paymentMethods(), 'create')) {
                if ($paymentMethodsEnabled) {
                    $this->supports[] = 'add_payment_method';
                }

                $this->supports[] = 'paymentMethods.create';
            }

            if (method_exists($provider->paymentMethods(), 'update')) {
                $this->supports[] = 'paymentMethods.update';
            }

            if (method_exists($provider->paymentMethods(), 'delete')) {
                $this->supports[] = 'paymentMethods.delete';
            }
        }

        foreach ($this->integrations as $integration) {
            $this->supports = ArrayHelper::combine($this->supports, $integration->getSupports());
        }
    }

    /**
     * Create a Customer.
     *
     * @since 2.10.0
     *
     * @param Customer $customer
     *
     * @return Customer
     * @throws Exception
     */
    public function createCustomer(Customer $customer): Customer
    {
        $createdCustomer = $this->getProvider()->customers()->create($customer);

        $dataStoreClass = $this->getBinding(CustomerDataStore::class);

        /** @var $dataStore CustomerDataStore */
        $dataStore = new $dataStoreClass($this->providerName);

        return $dataStore->save($createdCustomer);
    }

    /**
     * Create a Payment Method.
     *
     * @since 2.10.0
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    public function createPaymentMethod(AbstractPaymentMethod $paymentMethod): AbstractPaymentMethod
    {
        $createdPaymentMethod = $this->getProvider()->paymentMethods()->create($paymentMethod);

        $createdPaymentMethod->setProviderName($this->providerName);

        $dataStoreClass = $this->getBinding(PaymentMethodDataStore::class);

        $dataStore = new $dataStoreClass($this->providerName);

        return $dataStore->save($createdPaymentMethod);
    }

    /**
     * Delete a Customer.
     *
     * @since 2.10.0
     *
     * @param Customer $customer
     *
     * @return Customer
     * @throws Exception
     */
    public function deleteCustomer(Customer $customer): Customer
    {
        /** @var Customer $deletedCustomer */
        $deletedCustomer = $this->getProvider()->customers()->delete($customer);

        $dataStoreClass = $this->getBinding(CustomerDataStore::class);

        /** @var $dataStore CustomerDataStore */
        $dataStore = new $dataStoreClass($this->providerName);

        $dataStore->delete($deletedCustomer->getId());

        return $deletedCustomer;
    }

    /**
     * Delete a Payment Method.
     *
     * @since 2.10.0
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    public function deletePaymentMethod(AbstractPaymentMethod $paymentMethod): AbstractPaymentMethod
    {
        $deletedPaymentMethod = $this->getProvider()->paymentMethods()->delete($paymentMethod);

        $dataStoreClass = $this->getBinding(PaymentMethodDataStore::class);

        $dataStore = new $dataStoreClass($this->providerName);

        $dataStore->delete($deletedPaymentMethod->getId());

        return $deletedPaymentMethod;
    }

    /**
     * Gets a binding.
     *
     * @since 2.10.0
     *
     * @param string $className
     * @return string
     */
    protected function getBinding(string $className): string
    {
        $result = ArrayHelper::get($this->bindings, $className);

        return $result ?? $className;
    }

    /**
     * Gets the gateway integrations.
     *
     * @since 2.10.0
     *
     * @return array
     */
    public function getIntegrations(): array
    {
        return $this->integrations;
    }

    /**
     * Gets the provider for the gateway.
     *
     * @since 2.10.0
     *
     * @return AbstractProvider
     * @throws Exception
     */
    protected function getProvider(): AbstractProvider
    {
        return Payments::getInstance()->provider($this->providerName);
    }

    /**
     * Gets a payment method to add.
     *
     * @since 2.10.0
     *
     * @return AbstractPaymentMethod
     */
    abstract protected function getPaymentMethodForAdd(): AbstractPaymentMethod;

    /**
     * Builds a transaction for the given WooCommerce order and associated type.
     *
     * @since 2.10.0
     *
     * @param string $transactionType
     * @param WC_Order $wooOrder
     *
     * @return AbstractTransaction
     * @throws Exception
     */
    protected function buildTransactionForOrder(string $transactionType, WC_Order $wooOrder): AbstractTransaction
    {
        /** @var AbstractTransaction $transaction */
        $transaction = $this->setOrderTransactionProviderName(new $transactionType());

        $orderAdapterClass = $this->getBinding(OrderAdapter::class);
        $transaction->setOrder((new $orderAdapterClass($wooOrder))->convertFromSource());

        if ($wooCustomerId = (int) $wooOrder->get_customer_id()) {
            $transaction = $this->appendCustomerToTransaction($wooCustomerId, $transaction);
        }

        return $transaction;
    }

    /**
     * Sets the provider name for the given transaction.
     *
     * @param AbstractTransaction $transaction
     * @return AbstractTransaction
     */
    protected function setOrderTransactionProviderName(AbstractTransaction $transaction) : AbstractTransaction
    {
        if ($this->providerName) {
            $transaction->setProviderName($this->providerName);
        }

        return $transaction;
    }

    /**
     * Appends customer's data to transaction if set.
     *
     * @since 2.10.0
     *
     * @param int $wooCustomerId
     * @param AbstractTransaction $transaction
     *
     * @return AbstractTransaction
     * @throws Exception
     */
    protected function appendCustomerToTransaction(int $wooCustomerId, AbstractTransaction $transaction): AbstractTransaction
    {
        if ($wooCustomerId) {
            $wooCustomer = new WC_Customer($wooCustomerId);
            $customerAdapterClass = $this->getBinding(CustomerAdapter::class);
            $transaction->setCustomer((new $customerAdapterClass($wooCustomer))->convertFromSource());
        }

        return $transaction;
    }

    /**
     * Get a Transaction for Capture.
     *
     * @since 2.10.0
     *
     * @param WC_Order|null $order
     *
     * @return CaptureTransaction
     * @throws Exception
     */
    public function getTransactionForCapture(WC_Order $order): CaptureTransaction
    {
        /** @var CaptureTransaction $transaction */
        $transaction = $this->buildTransactionForOrder(CaptureTransaction::class, $order);

        $this->maybeSetRemoteParentId($order, $transaction);

        return $transaction->setTotalAmount(
            (new CurrencyAmountAdapter($order->get_total(), $order->get_currency()))->convertFromSource()
        );
    }

    /**
     * Get a Transaction for Payment.
     *
     * @since 2.10.0
     *
     * @param WC_Order|null $order
     *
     * @return PaymentTransaction
     * @throws Exception
     */
    public function getTransactionForPayment(WC_Order $order): PaymentTransaction
    {
        /** @var PaymentTransaction $transaction */
        $transaction = $this->buildTransactionForOrder(PaymentTransaction::class, $order);

        $paymentMethod = $this->findPaymentMethodFromToken($order->get_user_id());

        if ($paymentMethod) {
            $transaction->setPaymentMethod($paymentMethod);
        }

        if (
            $order->get_user_id()
            && ArrayHelper::get($_POST, 'mwc-payments-'.$this->providerName.'-tokenize-payment-method')
            && ! ArrayHelper::get($_POST, "mwc-payments-{$this->providerName}-payment-method-id", '')
        ) {
            $transaction->setShouldTokenize(true);
        }

        // issue auth-only transactions if configured to do so
        if (
            'authorization' === Configuration::get("payments.{$this->providerName}.transactionType")
            && (! Configuration::get("payments.{$this->providerName}.chargeVirtualOrders") || ! $this->orderIsVirtual($order))
        ) {
            $transaction->setAuthOnly(true);
        }

        return $transaction->setTotalAmount(
            (new CurrencyAmountAdapter($order->get_total(), $order->get_currency()))->convertFromSource()
        );
    }

    /**
     * Determines whether a WooCommerce order is virtual.
     *
     * @param WC_Order $order
     *
     * @return bool
     */
    protected function orderIsVirtual(WC_Order $order): bool
    {
        $isVirtual = true;

        /** @var \WC_Order_Item_Product $item */
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            // once we've found one non-virtual product we know we're done, break out of the loop
            if ($product && ! $product->is_virtual()) {
                $isVirtual = false;
                break;
            }
        }

        return $isVirtual;
    }

    /**
     * Get a Transaction for Refund.
     *
     * @since 2.10.0
     *
     * @param WC_Order|null $order
     *
     * @return RefundTransaction
     * @throws Exception
     */
    protected function getTransactionForRefund(WC_Order $order): RefundTransaction
    {
        /** @var RefundTransaction $transaction */
        $transaction = $this->buildTransactionForOrder(RefundTransaction::class, $order);

        $this->maybeSetRemoteParentId($order, $transaction);

        return $transaction;
    }

    /**
     * Gets a Transaction for Void.
     *
     * @param WC_Order|null $order
     * @return VoidTransaction
     * @throws Exception
     */
    public function getTransactionForVoid(WC_Order $order) : VoidTransaction
    {
        $transaction = $this->buildTransactionForOrder(VoidTransaction::class, $order);

        $this->maybeSetRemoteParentId($order, $transaction);

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $transaction->setTotalAmount(
            (new CurrencyAmountAdapter($order->get_total(), $order->get_currency()))->convertFromSource()
        );
    }

    /**
     * Determines if the gateway should be active for use.
     *
     * @return bool
     * @throws Exception
     */
    abstract public static function isActive(): bool;

    /**
     * Sets the order received text for the customer.
     *
     * @internal
     *
     * @param string $text
     * @param \WC_Order|Order $order (not used)
     * @return string
     * @throws Exception
     */
    public function maybeSetOrderReceivedText($text, $order)
    {
        $wc = WooCommerceRepository::getInstance();

        if ($wc && $wc->session && isset($wc->session->order_received_text)) {
            $newText = (string) $wc->session->order_received_text;

            if ($newText) {
                $text = $newText;
                unset($wc->session->order_received_text);
            }
        }

        return $text;
    }

    /**
     * Filters the gateway title when the context is the edit order screen when using a virtual terminal.
     *
     * @internal
     * @see WC_Payment_Gateway::get_title() callback
     * @see AbstractPaymentGateway::__construct()
     *
     * @param string $gatewayTitle
     * @param string $paymentGatewayId
     * @return string
     * @throws Exception
     */
    public function filterGatewayTitleBySource($gatewayTitle, $paymentGatewayId)
    {
        if (! WordPressRepository::isAdmin()) {
            return $gatewayTitle;
        }

        $screen = WordPressRepository::getCurrentScreen();
        $orderId = $screen ? $screen->getObjectId() : null;

        if (! $orderId || ! $paymentGatewayId || $paymentGatewayId !== $this->id || 'edit_order' !== $screen->getPageId()) {
            return $gatewayTitle;
        }

        $order = OrdersRepository::get($orderId);

        if (! $order) {
            return $gatewayTitle;
        }

        if ('virtual_terminal' === $order->get_created_via()) {
            $gatewayTitle = sprintf(
                /* translators: Placeholder: %s - payment gateway method title */
                __('%s Virtual Terminal', 'mwc-core'),
                (string) $this->get_method_title()
            );
        }

        return $gatewayTitle;
    }

    /**
     * May set the transaction remote parent ID if there's a transaction for the order.
     *
     * @since 2.10.0
     *
     * @param WC_Order $order
     * @param AbstractTransaction $transaction
     * @throws BaseException
     */
    protected function maybeSetRemoteParentId(WC_Order $order, AbstractTransaction $transaction)
    {
        try {
            $binding = $this->getBinding(OrderTransactionDataStore::class);

            $orderTransactionDataStore = new $binding($transaction->getProviderName() ?: $this->providerName);

            $orderTransaction = $orderTransactionDataStore->read($order->get_id(), 'payment');

            $transaction->setRemoteParentId($orderTransaction->getRemoteId());
        } catch (BaseException $ex) {
            // @TODO Handle GoDaddy\WordPress\MWC\Common\Exceptions\BaseException: Order not found {@acastro1 2021-05-14}
        } catch (Exception $ex) {
            throw new BaseException($ex->getMessage());
        }
    }

    /**
     * Process a Capture transaction.
     *
     * @since 2.10.0
     *
     * @param CaptureTransaction $transaction
     *
     * @return CaptureTransaction
     * @throws Exception
     */
    public function processCapture(CaptureTransaction $transaction): CaptureTransaction
    {
        $updatedTransaction = $this->getProvider()->transactions()->capture($transaction);

        $dataStoreClass = $this->getBinding(OrderCaptureTransactionDataStore::class);

        $dataStore = new $dataStoreClass($this->providerName);

        return $dataStore->save($updatedTransaction);
    }

    /**
     * Processes a payment.
     *
     * @param $order_id
     * @param AbstractPaymentMethod $paymentMethod
     * @return array
     */
    public function process_payment($order_id, AbstractPaymentMethod $paymentMethod = null): array
    {
        try {
            $wooOrder = OrdersRepository::get($order_id);

            if (! $wooOrder) {
                throw new BaseException('Order not found');
            }

            $transaction = $this->getTransactionForPayment($wooOrder);

            if ($paymentMethod) {
                $transaction->setPaymentMethod($paymentMethod);
            } elseif ($transaction->shouldTokenize()) {
                $transaction = $this->processTransactionCustomer($transaction);
                $transaction = $this->processTransactionPaymentMethod($transaction);
            }

            $beforeProcessPayment = apply_filters('mwc_payments_'.$this->providerName.'_before_process_payment', false, $wooOrder);

            if (false !== $beforeProcessPayment) {
                return $beforeProcessPayment;
            }

            /*
             * Fires after generating the payment transaction, before payment is processed.
             *
             * This allows actors to modify the transaction object as needed.
             *
             * @param PaymentTransaction $transaction
             * @param WC_Order $wooOrder
             */
            do_action("mwc_payments_{$this->providerName}_before_process_payment_transaction", $transaction, $wooOrder);

            $transaction = $this->processPayment($transaction);

            if ($transaction->getStatus()) {
                $this->processPaymentResult($transaction, $wooOrder);
            }

            return (array) apply_filters('mwc_payments_'.$this->providerName.'_after_process_payment', [
                'result'   => 'success',
                'redirect' => $this->get_return_url($wooOrder),
            ], $wooOrder, $transaction);
        } catch (Exception $exception) {
            return [
                'result'  => 'failure',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Gets new instance of message adapter for the given payment transaction.
     *
     * @param PaymentTransaction $transaction
     * @return PaymentTransactionMessageAdapter
     */
    protected function getPaymentTransactionMessageAdapter(PaymentTransaction $transaction) : PaymentTransactionMessageAdapter
    {
        return new PaymentTransactionMessageAdapter($transaction);
    }

    /**
     * Processes the payment result.
     *
     * Handles cleaning up the order & cart state.
     *
     * @param PaymentTransaction $transaction
     * @param WC_Order           $wooOrder
     *
     * @return WC_Order
     * @throws Exception
     */
    protected function processPaymentResult(PaymentTransaction $transaction, WC_Order $wooOrder): WC_Order
    {
        $customerMessage = $this->getPaymentTransactionMessageAdapter($transaction)->convertFromSource();

        $status = $transaction->getStatus();

        if ($status instanceof DeclinedTransactionStatus) {
            $wooOrder->update_status('failed');

            // add a notice if in the frontend
            if (function_exists('wc_add_notice')) {
                wc_add_notice($customerMessage, 'error');
            }

            throw new Exception('The transaction failed.');
        }

        if ($status instanceof ApprovedTransactionStatus && ! $transaction->isAuthOnly()) {
            $wooOrder->payment_complete($transaction->getRemoteId());
        } else {
            $wooOrder->update_status('on-hold');
            wc_reduce_stock_levels($wooOrder->get_id());
        }

        if (! Configuration::get('payments.poynt.onboarding.hasFirstPayment')) {
            update_option('mwc_payments_poynt_onboarding_hasFirstPayment', true);
            Configuration::set('payments.poynt.onboarding.hasFirstPayment', true);
        }

        // process_payment() can sometimes be called in an admin-context
        if (isset(WC()->cart)) {
            WC()->cart->empty_cart();
        }

        // store the customer's message for display on the Thank You page
        if (isset(WC()->session)) {
            WC()->session->order_received_text = $customerMessage;
        }

        return $wooOrder;
    }

    /**
     * Process a Payment Transaction.
     *
     * @since 2.10.0
     *
     * @param PaymentTransaction $transaction
     *
     * @return PaymentTransaction
     *
     * @throws Exception
     */
    public function processPayment(PaymentTransaction $transaction): PaymentTransaction
    {
        // only post a payment if there is a total
        if ($transaction->getTotalAmount() && $transaction->getTotalAmount()->getAmount()) {
            $transaction = $this->getProvider()->transactions()->pay($transaction);
        } elseif ($transaction->getPaymentMethod() && $transaction->getPaymentMethod()->getRemoteId()) {
            $transaction->setStatus(new ApprovedTransactionStatus());
        } else {
            $transaction->setStatus(new HeldTransactionStatus());
        }

        $dataStoreClass = $this->getBinding(OrderPaymentTransactionDataStore::class);

        $dataStore = new $dataStoreClass($this->providerName);

        return $dataStore->save($transaction);
    }

    /**
     * Processes a refund or void.
     *
     * @param mixed $order_id
     * @param int|float $amount
     * @param string $reason
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = null)
    {
        try {
            $wooOrder = OrdersRepository::get($order_id);

            if (! $wooOrder) {
                throw new BaseException('Order not found');
            }

            $orderAdapter = $this->getBinding(OrderAdapter::class);

            /** @var Order $order */
            $order = (new $orderAdapter($wooOrder))->convertFromSource();

            if ($order->isCaptured() || ! $this->supports('voids')) {
                $transaction = $this->getTransactionForRefund($wooOrder);
                $processMethod = 'processRefund';
            } else {
                $transaction = $this->getTransactionForVoid($wooOrder);
                $processMethod = 'processVoid';

                // ensure the order moves to the Cancelled status when the void is successful
                $this->filterVoidedStatus();
            }

            if (is_numeric($amount)) {
                $currencyAmount = (new CurrencyAmountAdapter($amount, $wooOrder->get_currency()))->convertFromSource();
                $transaction->setTotalAmount($currencyAmount);
            }

            if ($reason) {
                $transaction->setReason($reason);
            }

            $transaction = $this->$processMethod($transaction);

            if (! $transaction->getStatus() instanceof ApprovedTransactionStatus) {
                throw new BaseException($transaction->getResultMessage());
            }

            return true;
        } catch (Exception $exception) {
            return new WP_Error($exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * Registers a filter to change Woo's "fully refunded" status to Cancelled.
     *
     * This is only used when processing a void.
     *
     * @throws Exception
     */
    protected function filterVoidedStatus()
    {
        Register::filter()
            ->setGroup('woocommerce_order_fully_refunded_status')
            ->setHandler(function () {
                return 'cancelled';
            })
            ->execute();
    }

    /**
     * Process a refund transaction.
     *
     * @since 2.10.0
     *
     * @param RefundTransaction $transaction
     *
     * @return RefundTransaction
     * @throws Exception
     */
    public function processRefund(RefundTransaction $transaction): RefundTransaction
    {
        $updatedTransaction = $this->getProvider()->transactions()->refund($transaction);

        $dataStoreClass = $this->getBinding(OrderRefundTransactionDataStore::class);

        /** @var $dataStore OrderRefundTransactionDataStore */
        $dataStore = new $dataStoreClass($this->providerName);

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $dataStore->save($updatedTransaction);
    }

    /**
     * Process a payment transaction for a Customer.
     *
     * @since 2.10.0
     *
     * @param PaymentTransaction $transaction
     *
     * @return PaymentTransaction
     * @throws Exception
     *
     * TODO: update exception to to use Common/BaseException {@nmolham-godaddy - 2021-05-04}
     */
    protected function processTransactionCustomer(PaymentTransaction $transaction): PaymentTransaction
    {
        if (! $this->supports('customers')) {
            return $transaction;
        }

        if (! $customer = $transaction->getCustomer()) {
            return $transaction;
        }

        $updatedCustomer = $customer->getRemoteId() ? $this->updateCustomer($customer) : $this->createCustomer($customer);

        return $transaction->setCustomer($updatedCustomer);
    }

    /**
     * Process a transaction payment method.
     *
     * @since 2.10.0
     *
     * @param PaymentTransaction $transaction
     *
     * @return PaymentTransaction
     * @throws Exception
     */
    protected function processTransactionPaymentMethod(PaymentTransaction $transaction): PaymentTransaction
    {
        if (! $this->supports('tokenization')) {
            return $transaction;
        }

        $paymentMethod = $transaction->getPaymentMethod();

        if (null === $paymentMethod) {
            return $transaction;
        }

        if ($this->supports('paymentMethods.update') && $paymentMethod->getRemoteId()) {
            $paymentMethod = $this->updatePaymentMethod($paymentMethod);
        } elseif ($this->supports('paymentMethods.create')) {
            $paymentMethod = $this->createPaymentMethod($paymentMethod);
        }

        return $transaction->setPaymentMethod($paymentMethod);
    }

    /**
     * Process a Void transaction.
     *
     * @param VoidTransaction $transaction
     * @return VoidTransaction
     * @throws Exception
     */
    public function processVoid(VoidTransaction $transaction) : VoidTransaction
    {
        if ($transaction->getTotalAmount() && $transaction->getOrder() && $transaction->getTotalAmount()->getAmount() !== $transaction->getOrder()->getTotalAmount()->getAmount()) {
            throw new Exception('Oops, you cannot partially void this order. Please use the full order amount.', 400);
        }

        $updatedTransaction = $this->getProvider()->transactions()->void($transaction);

        $dataStoreClass = $this->getBinding(OrderVoidTransactionDataStore::class);

        /** @var $dataStore OrderVoidTransactionDataStore */
        $dataStore = new $dataStoreClass($this->providerName);

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $dataStore->save($updatedTransaction);
    }

    /**
     * Processes and saves options.
     *
     * Updates the configuration values after the new WooCommerce settings are saved.
     *
     * @return bool was anything saved?
     * @throws PaymentsProviderSettingsException
     */
    public function process_admin_options()
    {
        $result = parent::process_admin_options();

        $this->updateConfigurationFromSettings();

        return $result;
    }

    /**
     * Determines if the given feature is supported.
     *
     * @since 2.10.0
     *
     * @param mixed $feature
     *
     * @return bool
     */
    public function supports($feature)
    {
        // TODO: integrate with Configurations {@cwiseman 2021-05-18}

        return parent::supports($feature);
    }

    /**
     * Update a Customer.
     *
     * @since 2.10.0
     *
     * @param Customer $customer
     *
     * @return Customer
     * @throws Exception
     */
    public function updateCustomer(Customer $customer): Customer
    {
        $createdCustomer = $this->getProvider()->customers()->update($customer);

        $dataStoreClass = $this->getBinding(CustomerDataStore::class);

        /** @var $dataStore CustomerDataStore */
        $dataStore = new $dataStoreClass($this->providerName);

        return $dataStore->save($createdCustomer);
    }

    /**
     * Update a Payment Method.
     *
     * @since 2.10.0
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    public function updatePaymentMethod(AbstractPaymentMethod $paymentMethod): AbstractPaymentMethod
    {
        $updatedPaymentMethod = $this->getProvider()->paymentMethods()->update($paymentMethod);

        $dataStoreClass = $this->getBinding(PaymentMethodDataStore::class);

        $dataStore = new $dataStoreClass($this->providerName);

        return $dataStore->save($updatedPaymentMethod);
    }

    /**
     * Check for a payment method using a POSTed payment token id, otherwise get a new payment method instance.
     *
     * @param int $customerId
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    private function findPaymentMethodFromToken($customerId = 0): AbstractPaymentMethod
    {
        $paymentTokenId = (int) ArrayHelper::get($_POST, "mwc-payments-{$this->providerName}-payment-method-id", '');

        if ($paymentTokenId) {
            $dataStoreClass = $this->getBinding(PaymentMethodDataStore::class);
            $dataStore = new $dataStoreClass($this->providerName);
            $paymentMethod = $dataStore->read($paymentTokenId);

            // ensure the retrieved payment method belongs to the given customer ID
            if ((int) $paymentMethod->getCustomerId() !== (int) $customerId) {
                throw new Exception('Invalid payment method ID');
            }
        } else {
            $paymentMethod = $this->getPaymentMethodForAdd();
        }

        return $paymentMethod;
    }

    /**
     * Updates configuration values based on WooCommerce settings.
     *
     * @throws PaymentsProviderSettingsException
     */
    protected function updateConfigurationFromSettings($configurations = null)
    {
        if (empty($configurations)) {
            return;
        }

        foreach ($configurations as $configurationKey => $wooKey) {
            $settingValue = $this->get_option($wooKey);

            if (null === $settingValue || is_array($settingValue)) {
                continue;
            }

            if (ArrayHelper::contains(['yes', 'no'], $settingValue)) {
                $settingValue = 'yes' === $settingValue;
            }

            try {
                Configuration::set("payments.{$this->providerName}.{$configurationKey}", $settingValue);
            } catch (Exception $exception) {
                throw new PaymentsProviderSettingsException("Payments configuration {$configurationKey} could not be set with value {$settingValue}");
            }
        }
    }
}
