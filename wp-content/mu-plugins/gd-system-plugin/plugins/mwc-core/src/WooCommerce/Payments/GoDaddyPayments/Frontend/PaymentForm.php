<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use WC_Customer;
use WC_Order;

class PaymentForm extends \GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\PaymentForm
{
    /**
     * Registers the hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        parent::registerHooks();

        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'enqueueScripts'])
            ->execute();
    }

    /**
     * Enqueues the scripts.
     *
     * @internal
     *
     * @throws Exception
     */
    public function enqueueScripts()
    {
        $sdkUrl = ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.poynt.api.productionSdkUrl') : Configuration::get('payments.poynt.api.stagingSdkUrl');

        Enqueue::script()
            ->setHandle('poynt-collect-sdk')
            ->setSource($sdkUrl)
            ->execute();

        Enqueue::script()
            ->setHandle('mwc-payments-poynt-payment-form')
            ->setSource(WordPressRepository::getAssetsUrl('js/payments/frontend/poynt.js'))
            ->setDependencies(['jquery', 'poynt-collect-sdk'])
            ->attachInlineScriptObject('poyntPaymentFormI18n')
            ->attachInlineScriptVariables([
                'errorMessages' => [
                    'genericError'          => __('An error occurred, please try again or try an alternate form of payment.', 'mwc-core'),
                    'missingCardDetails'    => __('Missing card details.', 'mwc-core'),
                    'missingBillingDetails' => __('Missing billing details.', 'mwc-core'),
                ],
            ])
            ->execute();

        wc_enqueue_js(sprintf(
            'window.mwc_payments_poynt_payment_form_handler = new MWCPaymentsPoyntPaymentFormHandler(%s);',
            ArrayHelper::jsonEncode([
                'appId'            => Poynt::getAppId(),
                'businessId'       => Poynt::getBusinessId(),
                'customerAddress'  => $this->getCustomerAddress(),
                'isLoggingEnabled' => Configuration::get('mwc.debug'),
            ])
        ));
    }

    /**
     * Renders the payment fields.
     */
    protected function renderPaymentFields()
    {
        parent::renderPaymentFields();

        $nonceFieldId = 'mwc-payments-'.$this->providerName.'-payment-nonce'; ?>
        <div id="mwc-payments-poynt-hosted-form"></div>
        <input type="hidden" id="<?php echo esc_attr($nonceFieldId); ?>" name="<?php echo esc_attr($nonceFieldId); ?>">
        <?php
    }

    /**
     * Gets the current customer's address.
     *
     * @return array
     * @throws Exception
     */
    protected function getCustomerAddress() : array
    {
        $address = [
            'firstName' => '',
            'lastName'  => '',
            'line1'     => '',
            'postcode'  => '',
        ];

        // if on the checkout pay page use the order's address details
        if (WooCommerceRepository::isCheckoutPayPage()) {
            $order = OrdersRepository::get($this->getOrderPayQueryVar());

            if ($order instanceof WC_Order) {
                $address['firstName'] = $order->get_billing_first_name();
                $address['lastName'] = $order->get_billing_last_name();
                $address['line1'] = $order->get_billing_address_1();
                $address['postcode'] = $order->get_billing_postcode();
            }

            return $address;
        }

        // get the current customer's address details if available
        if (WC()->customer instanceof WC_Customer) {
            $address['firstName'] = WC()->customer->get_billing_first_name();
            $address['lastName'] = WC()->customer->get_billing_last_name();
            $address['line1'] = WC()->customer->get_billing_address_1();
            $address['postcode'] = WC()->customer->get_billing_postcode();
        }

        return $address;
    }

    /**
     * Gets order pay query var from request.
     *
     * @return int
     */
    protected function getOrderPayQueryVar() : int
    {
        global $wp;

        return (int) ($wp->query_vars['order-pay'] ?? 0);
    }
}
