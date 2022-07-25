<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;

class Onboarding
{
    /** @var string connected status */
    const STATUS_CONNECTED = 'CONNECTED';

    /** @var string connecting status */
    const STATUS_CONNECTING = 'CONNECTING';

    /** @var string not available status */
    const STATUS_DECLINED = 'DECLINED';

    /** @var string disconnected status */
    const STATUS_DISCONNECTED = 'DISCONNECTED';

    /** @var string incomplete status */
    const STATUS_INCOMPLETE = 'INCOMPLETE';

    /** @var string needs attention status */
    const STATUS_NEEDS_ATTENTION = 'NEEDS_ATTENTION';

    /** @var string pending status */
    const STATUS_PENDING = 'PENDING';

    /** @var string suspended status */
    const STATUS_SUSPENDED = 'SUSPENDED';

    /** @var string not available status */
    const STATUS_TERMINATED = 'TERMINATED';

    /**
     * Determines if the payment gateway can be enabled.
     *
     * @param string $status
     *
     * @return bool
     */
    public static function canEnablePaymentGateway(string $status) : bool
    {
        return ArrayHelper::contains([static::STATUS_CONNECTED, static::STATUS_NEEDS_ATTENTION], $status);
    }

    /**
     * Determines of the payment gateway can be managed.
     *
     * @param string $status
     *
     * @return bool
     */
    public static function canManagePaymentGateway(string $status) : bool
    {
        return ArrayHelper::contains([
            static::STATUS_CONNECTED,
            static::STATUS_DECLINED,
            static::STATUS_INCOMPLETE,
            static::STATUS_NEEDS_ATTENTION,
            static::STATUS_PENDING,
            static::STATUS_SUSPENDED,
        ], $status);
    }

    /**
     * Gets the signup URL.
     *
     * @param string $serviceId
     * @param string $redirectNonce
     * @return string
     * @throws Exception
     */
    public static function getSignupUrl(string $serviceId, string $redirectNonce) : string
    {
        $context = ArrayHelper::jsonEncode([
            'serviceId'     => $serviceId,
            'redirectNonce' => $redirectNonce,
        ]);

        $currentUser = User::getCurrent();

        $parameters = [
            'name'                => $currentUser ? trim($currentUser->getFirstName().' '.$currentUser->getLastName()) : '',
            'email'               => $currentUser && is_email($currentUser->getEmail()) ? $currentUser->getEmail() : '',
            'companyName'         => SiteRepository::getTitle(),
            'companyAddressLine1' => WooCommerceRepository::isWooCommerceActive() ? WC()->countries->get_base_address() : '', // TODO: consider wrapping these getters with a common repository {@cwiseman 2021-05-23}
            'companyAddressLine2' => WooCommerceRepository::isWooCommerceActive() ? WC()->countries->get_base_address_2() : '',
            'companyAddressZip'   => WooCommerceRepository::isWooCommerceActive() ? WC()->countries->get_base_postcode() : '',
            'companyAddressCity'  => WooCommerceRepository::isWooCommerceActive() ? WC()->countries->get_base_city() : '',
            'companyTerritory'    => WooCommerceRepository::isWooCommerceActive() ? WC()->countries->get_base_state() : '',
            'companyWebsite'      => esc_url(SiteRepository::getHomeUrl()),
            'context'             => base64_encode($context),
            'redirectUrl'         => StringHelper::trailingSlash(Configuration::get('mwc.extensions.api.url', '')).'payments/redirect',
            'serviceId'           => $serviceId,
            'serviceType'         => Configuration::get('payments.poynt.serviceType', ''),
        ];

        // point to the production or staging environments depending on the current site location
        if (ManagedWooCommerceRepository::isProductionEnvironment()) {
            $url = Configuration::get('payments.poynt.onboarding.productionUrl', '');
        } else {
            $url = Configuration::get('payments.poynt.onboarding.stagingUrl', '');
            $parameters['mock'] = 'true';
        }

        return add_query_arg(rawurlencode_deep(array_filter($parameters)), $url);
    }

    /**
     * Gets the onboarding status.
     *
     * @return string
     * @throws Exception
     */
    public static function getStatus() : string
    {
        return (string) Configuration::get('payments.poynt.onboarding.status', '');
    }

    /**
     * Gets the configured webhook secret.
     *
     * @return string
     * @throws Exception
     */
    public static function getWebhookSecret() : string
    {
        return (string) Configuration::get('payments.poynt.onboarding.webhookSecret', '');
    }

    /**
     * Determines if the onboarded account has deposits enabled.
     *
     * @return bool
     * @throws Exception
     */
    public static function depositsEnabled() : bool
    {
        return (bool) Configuration::get('payments.poynt.onboarding.depositsEnabled', false);
    }

    /**
     * Determines if the onboarded account has a bank account.
     *
     * @return bool
     * @throws Exception
     */
    public static function hasBankAccount() : bool
    {
        return (bool) Configuration::get('payments.poynt.onboarding.hasBankAccount', false);
    }

    /**
     * Determines if the onboarded account has payments enabled.
     *
     * @return bool
     * @throws Exception
     */
    public static function paymentsEnabled() : bool
    {
        return (bool) Configuration::get('payments.poynt.onboarding.paymentsEnabled', false);
    }

    /**
     * Determines if signup has been started.
     *
     * @return bool
     * @throws Exception
     */
    public static function isSignupStarted() : bool
    {
        return (bool) Configuration::get('payments.poynt.onboarding.signupStarted', false);
    }

    /**
     * Sets whether the account has deposits enabled.
     *
     * @param bool $value
     *
     * @throws Exception
     */
    public static function setDepositsEnabled(bool $value)
    {
        update_option('mwc_payments_poynt_onboarding_depositsEnabled', $value);

        Configuration::set('payments.poynt.onboarding.depositsEnabled', $value);
    }

    /**
     * Sets whether the account has a bank account.
     *
     * @param bool $value
     *
     * @throws Exception
     */
    public static function setHasBankAccount(bool $value)
    {
        update_option('mwc_payments_poynt_onboarding_hasBankAccount', $value);

        Configuration::set('payments.poynt.onboarding.hasBankAccount', $value);
    }

    /**
     * Sets whether the account has payments enabled.
     *
     * @param bool $value
     *
     * @throws Exception
     */
    public static function setPaymentsEnabled(bool $value)
    {
        update_option('mwc_payments_poynt_onboarding_paymentsEnabled', $value);

        Configuration::set('payments.poynt.onboarding.paymentsEnabled', $value);
    }

    /**
     * Sets the onboarding status.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setStatus(string $value)
    {
        update_option('mwc_payments_poynt_onboarding_status', $value);

        Configuration::set('payments.poynt.onboarding.status', $value);
    }

    /**
     * Sets the webhook secret.
     *
     * @param string $value
     *
     * @throws Exception
     */
    public static function setWebhookSecret(string $value)
    {
        update_option('mwc_payments_poynt_onboarding_webhookSecret', $value);

        Configuration::set('payments.poynt.onboarding.webhookSecret', $value);
    }

    /**
     * Checks if the site has any enabled payment gateways.
     *
     * @since 2.10.0
     *
     * @return bool
     */
    protected static function hasEnabledPaymentGateways() : bool
    {
        if (! function_exists('WC')) {
            return false;
        }

        return count(WC()->payment_gateways()->get_available_payment_gateways()) > 0;
    }

    /**
     * Checks if GDP should be auto-enabled or not.
     *
     * @since z.y.z
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldAutoEnablePaymentGateway() : bool
    {
        return static::STATUS_CONNECTED === static::getStatus() &&
            Configuration::get('godaddy.temporary_domain') &&
            'yes' !== get_option('mwc_payments_poynt_onboarding_auto_enabled', 'no') &&
            ! static::hasEnabledPaymentGateways();
    }
}
