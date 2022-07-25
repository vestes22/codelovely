<?php

namespace GoDaddy\WordPress\MWC\Core\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Payments\Models\StoreDevice;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\PoyntStoreDeviceFirstActivatedEvent;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\StoreDeviceAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\StoreDevicesRequest;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;

class Poynt
{
    /** @var array */
    const IN_PERSON_SHIPPING_METHOD_IDS = ['local_pickup', 'local_pickup_plus', 'mwc_local_delivery'];

    /**
     * Determines if Poynt is enabled.
     *
     * @return bool
     * @throws Exception
     */
    public static function isEnabled() : bool
    {
        return (bool) Configuration::get('payments.poynt.enabled', false);
    }

    /**
     * Gets the configured app ID.
     *
     * @return string
     * @throws Exception
     */
    public static function getAppId(): string
    {
        return (string) Configuration::get('payments.poynt.appId', '');
    }

    /**
     * Gets the configured application ID.
     *
     * Note: this represents the merchant's application to process payments, not the developer app ID for API communication.
     *
     * @return string
     * @throws Exception
     */
    public static function getApplicationId(): string
    {
        return (string) Configuration::get('payments.poynt.applicationId', '');
    }

    /**
     * Gets the configured business ID.
     *
     * @return string
     * @throws Exception
     */
    public static function getBusinessId(): string
    {
        return (string) Configuration::get('payments.poynt.businessId', '');
    }

    /**
     * Gets the GoDaddy Payments Hub URL.
     *
     * @return string
     * @throws Exception
     */
    public static function getHubUrl(): string
    {
        return (string) ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.poynt.hub.productionUrl', '') : Configuration::get('payments.poynt.hub.stagingUrl', '');
    }

    /**
     * Checks if GoDaddy Payments is connected.
     *
     * @return bool
     * @throws Exception
     */
    public static function isConnected(): bool
    {
        return
        (bool) Onboarding::canEnablePaymentGateway(Onboarding::getStatus())
        && Poynt::getAppId()
        && Poynt::getBusinessId()
        && Poynt::getPrivateKey();
    }

    /**
     * Gets the GoDaddy Payments private key.
     *
     * @return string
     * @throws Exception
     */
    public static function getPrivateKey(): string
    {
        return (string) Configuration::get('payments.poynt.privateKey', '');
    }

    /**
     * Gets the GoDaddy Payments public key.
     *
     * @return string
     * @throws Exception
     */
    public static function getPublicKey(): string
    {
        return (string) Configuration::get('payments.poynt.publicKey', '');
    }

    /**
     * Gets the configured service ID.
     *
     * @return string
     * @throws Exception
     */
    public static function getServiceId(): string
    {
        return (string) Configuration::get('payments.poynt.serviceId', '');
    }

    /**
     * Gets the Poynt API webhook secret. This secret is passed during Webhook
     * registration calls, and is used by Poynt to sign outgoing webhooks, and
     * by us to verify them.
     *
     * @return string
     */
    public static function getWebhookSecret() : string
    {
        if (! $webhookSecret = Configuration::get('payments.poynt.webhookSecret', '')) {
            $webhookSecret = StringHelper::generateUuid4();
            Configuration::set('payments.poynt.webhookSecret', $webhookSecret);
            update_option('mwc_payments_poynt_webhookSecret', $webhookSecret);
        }

        return (string) $webhookSecret;
    }

    /**
     * Sets the app ID.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setAppId(string $value)
    {
        update_option('mwc_payments_poynt_appId', $value);

        Configuration::set('payments.poynt.appId', $value);
    }

    /**
     * Sets the application ID.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setApplicationId(string $value)
    {
        update_option('mwc_payments_poynt_applicationId', $value);

        Configuration::set('payments.poynt.applicationId', $value);
    }

    /**
     * Sets the business ID.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setBusinessId(string $value)
    {
        update_option('mwc_payments_poynt_businessId', $value);

        Configuration::set('payments.poynt.businessId', $value);
    }

    /**
     * Sets the private key.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setPrivateKey(string $value)
    {
        update_option('mwc_payments_poynt_privateKey', $value);

        Configuration::set('payments.poynt.privateKey', $value);
    }

    /**
     * Sets the public key.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setPublicKey(string $value)
    {
        update_option('mwc_payments_poynt_publicKey', $value);

        Configuration::set('payments.poynt.publicKey', $value);
    }

    /**
     * Sets the service ID.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setServiceId(string $value)
    {
        update_option('mwc_payments_poynt_serviceId', $value);

        Configuration::set('payments.poynt.serviceId', $value);
    }

    /**
     * Determines if the user has any activated Poynt smart terminal.
     *
     * @param StoreDevice[] $devices
     * @throws Exception
     */
    public static function checkActivatedDevices(array $devices = [])
    {
        if (static::hasPoyntSmartTerminalActivated()) {
            return;
        }

        if (empty($devices)) {
            $devices = static::getStoreDevices();
        }

        foreach ($devices as $device) {
            /** @var StoreDevice $device */
            if (! $device->isActivePoyntSmartTerminal()) {
                continue;
            }

            Events::broadcast(new PoyntStoreDeviceFirstActivatedEvent($device));

            update_option('mwc_payments_payinperson_terminal_activated', true);
            Configuration::set('payments.godaddy-payments-payinperson.hasTerminalActivated', true);

            // @NOTE: Return early here as we have already set the intended cache options
            return;
        }

        update_option('mwc_payments_payinperson_terminal_activated', false);
        Configuration::set('payments.godaddy-payments-payinperson.hasTerminalActivated', false);
    }

    /**
     * Gets the store ID from the devices and saves it.
     *
     * @param StoreDevice[] $devices
     * @throws Exception
     */
    public static function setStoreId(array $devices = [])
    {
        if (! empty(Configuration::get('payments.poynt.storeId'))) {
            return;
        }

        if (empty($devices)) {
            $devices = static::getStoreDevices();
        }

        foreach ($devices as $device) {
            /** @var StoreDevice $device */
            if (! $device->isActivePoyntSmartTerminal()) {
                continue;
            }

            update_option('mwc_payments_poynt_storeId', $device->getStoreId());
            Configuration::set('payments.poynt.storeId', $device->getStoreId());

            return;
        }
    }

    /**
     * Determines if the site has any Poynt smart terminal devices activated in the configurations.
     *
     * @return bool
     * @throws Exception
     */
    public static function hasPoyntSmartTerminalActivated(): bool
    {
        return (bool) Configuration::get('payments.godaddy-payments-payinperson.hasTerminalActivated', false);
    }

    /**
     * Gets the store devices from Poynt API.
     *
     * @return array
     * @throws Exception
     */
    public static function getStoreDevices() : array
    {
        $devices = [];
        $response = (new StoreDevicesRequest())->send();
        $body = $response->getBody();

        if (! empty($body) && $response->getStatus() === 200) {
            foreach (ArrayHelper::wrap(ArrayHelper::get($body[0], 'storeDevices', [])) as $storeDevice) {
                /* @var StoreDevice[] */
                $devices[] = (new StoreDeviceAdapter($storeDevice))->convertFromSource();
            }
        }

        return $devices;
    }

    /**
     * Returns true if the supplied order meets the criteria to be pushed to the
     * Poynt API.
     *
     * Note: should this code live elsewhere? Is Poynt in danger of becoming a God object?
     *
     * @param Order $order
     * @return bool
     * @throws Exception
     */
    public static function shouldPushOrderDetailsToPoynt(Order $order)
    {

        // don't send the event the BOPIT feature is disabled
        if (! Configuration::get('features.bopit', false)) {
            return false;
        }

        // bail if status is not connected or suspended
        if (Onboarding::getStatus() !== 'CONNECTED' && Onboarding::getStatus() !== 'SUSPENDED') {
            return false;
        }

        // bail if shop has doesn't have at least one terminal connected
        if (! static::hasPoyntSmartTerminalActivated()) {
            return false;
        }

        // bail if not ordered with our shipping methods
        if (! $order->hasShippingMethod(static::IN_PERSON_SHIPPING_METHOD_IDS)) {
            return false;
        }

        return true;
    }
}
