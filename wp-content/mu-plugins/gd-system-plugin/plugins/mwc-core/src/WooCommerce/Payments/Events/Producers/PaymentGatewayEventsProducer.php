<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayDisabledEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayEnabledEvent;
use WC_Payment_Gateway;

/**
 * Class PaymentGatewaysEventsProducer.
 */
class PaymentGatewayEventsProducer implements ProducerContract
{
    /**
     * Sets up the events.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public function setup()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.18.1', __CLASS__.'::load');

        $this->load();
    }

    /**
     * Broadcasts an event whenever the merchant manually enables or disables a payment gateway.
     *
     * @internal
     *
     * @param array $newSettings the new settings for the payment gateway
     * @return array
     */
    public function maybeBroadcastPaymentGatewayEvents($newSettings)
    {
        if (! $gatewayId = str_replace('woocommerce_settings_api_sanitized_fields_', '', current_filter())) {
            return $newSettings;
        }

        if (! $this->didUserManuallyTriggeredPaymentGatewaySettingsChange($gatewayId)) {
            return $newSettings;
        }

        if (! $gateway = $this->getWooCommerceGateway($gatewayId)) {
            return $newSettings;
        }

        $oldSettings = get_option($gateway->get_option_key(), []);

        if (ArrayHelper::get($oldSettings, 'enabled') !== 'yes' && ArrayHelper::get($newSettings, 'enabled') === 'yes') {
            Events::broadcast(new PaymentGatewayEnabledEvent($gateway->id));
        } elseif (ArrayHelper::get($oldSettings, 'enabled') === 'yes' && ArrayHelper::get($newSettings, 'enabled') === 'no') {
            Events::broadcast(new PaymentGatewayDisabledEvent($gateway->id));
        }

        return $newSettings;
    }

    /**
     * Determines whether the current requests indicates that the merchant triggered a settings change for a payment gateway.
     *
     * @param string $gatewayId
     * @return bool
     */
    protected function didUserManuallyTriggeredPaymentGatewaySettingsChange(string $gatewayId) : bool
    {
        if (ArrayHelper::has($_POST, "woocommerce_{$gatewayId}_title")) {
            // submitted the settings form
            return true;
        }

        if (ArrayHelper::get($_POST, 'action') === 'woocommerce_toggle_gateway_enabled') {
            // interacted with the enabled/disabled toggle on the payment methods table
            return true;
        }

        if (ArrayHelper::get($_REQUEST, 'action') === OnboardingEventsProducer::ACTION_ENABLE_PAYMENT_METHOD) {
            // clicked the Enabled GoDaddy Payments link on the connected notice
            return true;
        }

        return false;
    }

    /**
     * @return WC_Payment_Gateway|null
     */
    protected function getWooCommerceGateway(string $id)
    {
        if (! $woocommerce = WooCommerceRepository::getInstance()) {
            return null;
        }

        if (! $gateways = $woocommerce->payment_gateways()) {
            return null;
        }

        return ArrayHelper::get($gateways->payment_gateways(), $id);
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function load()
    {
        if (empty($wc = WooCommerceRepository::getInstance())) {
            return;
        }

        foreach ($wc->payment_gateways()->payment_gateways() as $gateway) {
            Register::action()
                ->setGroup('woocommerce_settings_api_sanitized_fields_'.$gateway->id)
                ->setHandler([$this, 'maybeBroadcastPaymentGatewayEvents'])
                ->execute();
        }
    }
}
