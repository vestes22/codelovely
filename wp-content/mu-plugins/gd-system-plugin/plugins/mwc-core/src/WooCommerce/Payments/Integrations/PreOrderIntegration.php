<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\PaymentMethodDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use WC_Order;
use WC_Pre_Orders;
use WC_Pre_Orders_Cart;
use WC_Pre_Orders_Order;
use WC_Pre_Orders_Product;

/**
 * Pre-Order Integration.
 */
class PreOrderIntegration extends AbstractTokenizedIntegration
{
    /**
     * Completes an initial Pre-Order "pay later" payment.
     *
     * Pre-Orders requires us to mark the order as "pre-ordered" when our payment processing is complete.
     *
     * @internal
     *
     * @param mixed $result
     * @param mixed $wooOrder
     *
     * @return mixed
     */
    public function completeInitialPayment($result, $wooOrder)
    {
        if ($wooOrder instanceof WC_Order && $this->isOrderChargeUponRelease($wooOrder)) {
            WC_Pre_Orders_Order::mark_order_as_pre_ordered($wooOrder);
        }

        return $result;
    }

    /**
     * Gets the payment method for the given release payment order.
     *
     * TODO: this should become an adapter of its own {@cwiseman 2021-06-02}
     *
     * @param WC_Order $wooOrder
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    protected function getPaymentMethodForReleasePayment(WC_Order $wooOrder) : AbstractPaymentMethod
    {
        $paymentMethodRemoteId = $wooOrder->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_remoteId");

        if (! $paymentMethodRemoteId) {
            throw new Exception('Payment token is missing.');
        }

        // if a stored method was used, retrieve it
        if ($paymentMethodId = $wooOrder->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_id")) {
            $paymentMethod = $this->getStoredPaymentMethod($paymentMethodId);

            // only proceed if the method belongs to the order's customer
            if ($paymentMethod->getRemoteId() !== $paymentMethodRemoteId || (int) $paymentMethod->getCustomerId() !== (int) $wooOrder->get_user_id()) {
                throw new Exception('Payment token is invalid.');
            }

            return $paymentMethod;
        }

        // otherwise this was a guest, so just set a blank method with remote ID
        return $this->getPaymentMethodFromOrder($paymentMethodRemoteId, $wooOrder);
    }

    /**
     * Gets a payment method from data stored in order meta.
     *
     * TODO: this should become an adapter of its own {@cwiseman 2021-06-02}
     *
     * @param string   $remoteId
     * @param WC_Order $wooOrder
     *
     * @return AbstractPaymentMethod
     */
    protected function getPaymentMethodFromOrder(string $remoteId, WC_Order $wooOrder) : AbstractPaymentMethod
    {
        $paymentMethod = new CardPaymentMethod();
        $paymentMethod->setCreatedAt(new DateTime()) // ensure the library knows this is a permanent method, just without storage
            ->setExpirationMonth((string) $wooOrder->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_expirationMonth"))
            ->setExpirationYear((string) $wooOrder->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_expirationYear"))
            ->setLastFour((string) $wooOrder->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_lastFour"))
            ->setRemoteId($remoteId);

        return $paymentMethod;
    }

    /**
     * Gets a stored payment method for the given ID.
     *
     * @param int $id
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    protected function getStoredPaymentMethod(int $id) : AbstractPaymentMethod
    {
        return (new PaymentMethodDataStore($this->getGateway()->id))->read($id);
    }

    /**
     * Gets Pre-Order integration's supports.
     *
     * @return array
     */
    public function getSupports() : array
    {
        return ['pre-orders'];
    }

    /**
     * Determines if Pre-Orders is available.
     *
     * @return bool
     */
    protected function isAvailable() : bool
    {
        return class_exists(WC_Pre_Orders::class);
    }

    /**
     * Determines whether the given order is charged at a later date.
     *
     * @param WC_Order $wooOrder
     *
     * @return bool
     */
    protected function isOrderChargeUponRelease(WC_Order $wooOrder) : bool
    {
        return WC_Pre_Orders_Order::order_contains_pre_order($wooOrder) && WC_Pre_Orders_Order::order_requires_payment_tokenization($wooOrder);
    }

    /**
     * Processes initial payment for a pre-order.
     *
     * @internal
     *
     * @param mixed $transaction
     * @param mixed $wooOrder
     */
    public function processInitialPaymentTransaction($transaction, $wooOrder)
    {
        if (! $transaction instanceof PaymentTransaction) {
            return;
        }

        if ($wooOrder instanceof WC_Order && $this->isOrderChargeUponRelease($wooOrder)) {
            $transaction->getTotalAmount()->setAmount(0);
        }
    }

    /**
     * Processes release payment for the pre-order.
     *
     * @internal
     *
     * @param mixed $wooOrder
     */
    public function processReleasePayment($wooOrder)
    {
        try {
            if (! $wooOrder instanceof WC_Order) {
                throw new Exception('Order is invalid');
            }

            $result = $this->getGateway()->process_payment($wooOrder->get_id(), $this->getPaymentMethodForReleasePayment($wooOrder));

            if ('success' !== ArrayHelper::get($result, 'result')) {
                throw new Exception(ArrayHelper::get($result, 'message', ''));
            }
        } catch (Exception $exception) {
            if ($wooOrder instanceof WC_Order) {
                $wooOrder->update_status('failed', sprintf(
                    __('Pre-Order release payment failed: %s', 'mwc-core'),
                    $exception->getMessage()
                ));
            }
        }
    }

    /**
     * Registers the action & filter hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        parent::registerHooks();

        Register::action()
            ->setGroup('mwc_payments_'.$this->getGateway()->id.'_before_process_payment_transaction')
            ->setHandler([$this, 'processInitialPaymentTransaction'])
            ->setArgumentsCount(2)
            ->execute();

        Register::filter()
            ->setGroup('mwc_payments_'.$this->getGateway()->id.'_after_process_payment')
            ->setHandler([$this, 'completeInitialPayment'])
            ->setArgumentsCount(2)
            ->execute();

        Register::action()
            ->setGroup('wc_pre_orders_process_pre_order_completion_payment_'.$this->getGateway()->id)
            ->setHandler([$this, 'processReleasePayment'])
            ->setArgumentsCount(1)
            ->execute();
    }

    /**
     * Determines whether tokenization should be forced for the current cart.
     *
     * This is true if the cart contains a pre-order and it is charged on release.
     *
     * @return bool
     */
    protected function shouldForceCartTokenization() : bool
    {
        return WC_Pre_Orders_Cart::cart_contains_pre_order() && WC_Pre_Orders_Product::product_is_charged_upon_release(WC_Pre_Orders_Cart::get_pre_order_product());
    }

    /**
     * Determines whether the given order should force tokenization.
     *
     * This is true if the order contains a pre-order and it is charged on release.
     *
     * @param WC_Order $wooOrder
     *
     * @return bool
     */
    protected function shouldForceOrderTokenization(WC_Order $wooOrder) : bool
    {
        return $this->isOrderChargeUponRelease($wooOrder);
    }
}
