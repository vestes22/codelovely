<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\PaymentMethodDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\AbstractPaymentGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations\Contracts\IntegrationContract;
use WC_Order;
use WC_Subscription;
use WC_Subscriptions_Cart;
use WC_Subscriptions_Change_Payment_Gateway;

/**
 * Subscriptions Integration.
 */
class SubscriptionsIntegration implements IntegrationContract
{
    /** @var string */
    protected $orderPaymentMetaKey;

    /** @var AbstractPaymentGateway */
    protected $gateway;

    /**
     * SubscriptionsIntegration constructor.
     *
     * @param AbstractPaymentGateway $gateway
     *
     * @throws Exception
     */
    public function __construct(AbstractPaymentGateway $gateway)
    {
        if (! class_exists('WC_Subscriptions')) {
            return;
        }

        $this->gateway = $gateway;

        $this->orderPaymentMetaKey = "_{$gateway->id}_payment";

        $this->registerHooks();
    }

    /**
     * Registers actions and filters hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::filter()
            ->setGroup('mwc_payments_force_tokenization')
            ->setArgumentsCount(2)
            ->setHandler([$this, 'maybeForceTokenization'])
            ->execute();

        Register::filter()
            ->setGroup("mwc_payments_{$this->getGateway()->id}_after_process_payment")
            ->setArgumentsCount(3)
            ->setHandler([$this, 'processChangePayment'])
            ->execute();

        Register::action()
            ->setGroup("woocommerce_scheduled_subscription_payment_{$this->getGateway()->id}")
            ->setArgumentsCount(2)
            ->setHandler([$this, 'processRenewalPayment'])
            ->execute();

        Register::filter()
            ->setGroup("mwc_payments_{$this->getGateway()->id}_after_process_payment")
            ->setArgumentsCount(2)
            ->setHandler([$this, 'saveSubscriptionMetaData'])
            ->execute();

        Register::action()
            ->setGroup("woocommerce_subscription_failing_payment_method_updated_{$this->getGateway()->id}")
            ->setArgumentsCount(2)
            ->setHandler([$this, 'processFailedPaymentMethodUpdate'])
            ->execute();
    }

    /**
     * Gets the gateway instance.
     *
     * @return AbstractPaymentGateway|null
     */
    protected function getGateway()
    {
        return $this->gateway;
    }

    /**
     * Maybe forces tokenization.
     *
     * @internal callback
     *
     * @param mixed $isForced
     * @param mixed $gatewayId
     * @return bool|mixed|null
     * @throws Exception
     */
    public function maybeForceTokenization($isForced, $gatewayId)
    {
        if ($isForced || $gatewayId !== $this->getGateway()->id) {
            return $isForced;
        }

        if (WooCommerceRepository::isCheckoutPayPage() && $orderId = $this->getCurrentQueryOrderId()) {
            $order = OrdersRepository::get($orderId);
            $isForced = $order && $order->get_payment_method() === $this->getGateway()->id && wcs_order_contains_subscription($order->get_id());
        }

        if ($this->cartHasSubscriptionOrRenewal()) {
            return true;
        }

        return $isForced;
    }

    /**
     * Determines if Cart has subscription or renewal or is change payment request.
     *
     * @return bool
     */
    protected function cartHasSubscriptionOrRenewal() : bool
    {
        return WC_Subscriptions_Cart::cart_contains_subscription() ||
            wcs_cart_contains_renewal() ||
            WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment;
    }

    /**
     * Gets current/main query Order ID.
     *
     * @return int
     */
    protected function getCurrentQueryOrderId() : int
    {
        global $wp;

        return (int) ArrayHelper::get($wp->query_vars, 'order-pay', 0);
    }

    /**
     * Processes the result of a change payment.
     *
     * @internal
     *
     * @param $result
     * @param $subscription
     * @param $transaction
     *
     * @return array
     */
    public function processChangePayment($result, $subscription, $transaction)
    {
        // if this is not a subscription and not changing payment, bail for normal order processing
        if (
            ! $transaction instanceof PaymentTransaction
            || ! wcs_is_subscription($subscription)
            || ! did_action('woocommerce_subscription_change_payment_method_via_pay_shortcode')
        ) {
            return $result;
        }

        // if the transaction has no payment method, something went wrong
        if (! $transaction->getPaymentMethod() || ! $transaction->getPaymentMethod()->getRemoteId()) {
            wc_add_notice(__('An error occurred, please try again or try an alternate form of payment.', 'mwc-core'), 'error');

            return [
                'result' => 'failure',
            ];
        }

        return [
            'result'   => 'success',
            'redirect' => $subscription->get_view_order_url(),
        ];
    }

    /**
     * Processes renewal payment for a subscription.
     *
     * @internal
     *
     * @param mixed $amount
     * @param mixed $order
     */
    public function processRenewalPayment($amount, $order)
    {
        try {
            if (! $order instanceof WC_Order) {
                throw new Exception('Order is invalid');
            }

            $providerName = $this->getGateway()->id;
            $id = $order->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_id");
            $remoteId = $order->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_remoteId");

            if (! is_numeric($id) || ! $remoteId) {
                throw new Exception('Payment token is missing.');
            }

            $paymentMethod = $this->getPaymentMethodDataStore($providerName)->read($id);

            if ($paymentMethod->getRemoteId() !== $remoteId || (int) $paymentMethod->getCustomerId() !== (int) $order->get_user_id()) {
                throw new Exception('Payment token is invalid.');
            }

            $this->getGateway()->process_payment($order->get_id(), $paymentMethod);
        } catch (Exception $exception) {
            $order->update_status('failed', 'Subscription renewal: '.$exception->getMessage());
        }
    }

    /**
     * Gets payment method data store instance for the given provider.
     *
     * @param string $providerName
     * @return PaymentMethodDataStore
     */
    protected function getPaymentMethodDataStore(string $providerName) : PaymentMethodDataStore
    {
        return new PaymentMethodDataStore($providerName);
    }

    /**
     * Processes failed payment method update for a subscription.
     *
     * @internal
     *
     * @param mixed $subscription
     * @param mixed $renewalOrder
     */
    public function processFailedPaymentMethodUpdate($subscription, $renewalOrder)
    {
        // if the order doesn't have a transaction date stored, bail
        // this prevents updating the subscription with a failing token in case the merchant is switching the order status manually without new payment
        if (
            ! $subscription instanceof WC_Subscription
            || ! $renewalOrder instanceof WC_Order
            || ! $renewalOrder->get_meta("{$this->orderPaymentMetaKey}_createdAt")
        ) {
            return;
        }

        $this->setSubscriptionMetaFromOrder($subscription, $renewalOrder);
    }

    /**
     * Saves payment meta data after processing.
     *
     * @internal
     *
     * @param mixed $result
     * @param mixed $wooOrder WooCommerce order
     *
     * @return mixed
     */
    public function saveSubscriptionMetaData($result, $wooOrder)
    {
        if (! $wooOrder instanceof WC_Order) {
            return $result;
        }

        $subscriptions = wcs_get_subscriptions_for_order($wooOrder, [
            'order_type' => ['any'],
        ]);

        foreach ($subscriptions as $subscription) {
            $this->setSubscriptionMetaFromOrder($subscription, $wooOrder);
        }

        return $result;
    }

    /**
     * Sets the metadata necessary for Subscriptions to process a renewal.
     *
     * @param WC_Subscription $subscription
     * @param WC_Order $order
     */
    protected function setSubscriptionMetaFromOrder(WC_Subscription $subscription, WC_Order $order)
    {
        $subscription->update_meta_data("{$this->orderPaymentMetaKey}_paymentMethod_id", $order->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_id"));
        $subscription->update_meta_data("{$this->orderPaymentMetaKey}_paymentMethod_remoteId", $order->get_meta("{$this->orderPaymentMetaKey}_paymentMethod_remoteId"));
        $subscription->save_meta_data();
    }

    /**
     * Gets Subscriptions integration's supports.
     *
     * @return array
     */
    public function getSupports() : array
    {
        return [
            'subscriptions',
            'subscription_suspension',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'multiple_subscriptions',
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
        ];
    }
}
