<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use GoDaddy\WordPress\MWC\Core\Payments\API;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayFirstActiveEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\PaymentGatewayEventsProducer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\ExternalCheckout;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\MyPaymentMethods;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\PaymentMethodsListTable;

/**
 * Core payment gateways.
 *
 * Takes care of the necessary tasks for adding the core gateway(s) in a way that WooCommerce understands.
 */
class CorePaymentGateways
{
    use IsConditionalFeatureTrait;

    /** @var string[] classes to load as universal handlers */
    private $handlerClasses = [
        Captures::class,
        PaymentMethodsListTable::class,
        MyPaymentMethods::class,
        VirtualTerminal::class,
    ];

    /** @var string[] payments gateways to load */
    protected static $paymentGatewayClasses = [
        GoDaddyPaymentsGateway::class,
    ];

    /** @var AbstractPaymentGateway[] */
    private static $paymentGateways = [];

    /**
     * Core payment gateways constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addHooks();

        if (Onboarding::STATUS_CONNECTED === Onboarding::getStatus()) {
            static::$paymentGatewayClasses[] = GoDaddyPayInPersonGateway::class;
        }
    }

    /**
     * Loads the payments handlers.
     *
     * @internal callback
     * @see CorePaymentGateways::addHooks()
     *
     * @throws Exception
     */
    public function loadHandlers()
    {
        // don't load anything if we don't have any gateways enabled
        if (empty(static::getPaymentGateways())) {
            return;
        }

        foreach ($this->handlerClasses as $class) {
            new $class();
        }

        // TODO: load these as components once this class itself uses HasComponentsTrait {cwiseman 2021-10-21}
        (new OnboardingEventsProducer())->load();
        (new PaymentGatewayEventsProducer())->load();
        (new API())->load();

        if (ExternalCheckout::shouldLoad()) {
            (new ExternalCheckout())->load();
        }
    }

    /**
     * Adds instances of the gateways contained in this class to WooCommerce gateways.
     *
     * @internal callback
     * @see CorePaymentGateways::addHooks()
     * @see ApplePayGateway
     *
     * @param array|mixed $wcGateways
     * @return array|mixed
     * @throws Exception
     */
    public function loadPaymentGateways($wcGateways)
    {
        if (! ArrayHelper::accessible($wcGateways)) {
            return $wcGateways;
        }

        // show GDP items on the top of the list
        $gdGateways = ArrayHelper::wrap(static::getPaymentGateways());

        if ($payInPersonGateway = ArrayHelper::get($gdGateways, 'godaddy-payments-payinperson')) {
            $gdGateways = $gdGateways + ['godaddy-payments-payinperson' => $payInPersonGateway];
        }

        // Apple Pay is loaded as an ordinary WooCommerce gateway as it does not extend MWC abstract gateway model
        if (ApplePayGateway::isActive()) {
            $gdGateways = $gdGateways + ['godaddy-payments-apple-pay' => $this->getApplePayGatewayInstance()];
        }

        return array_unique($gdGateways + $wcGateways, SORT_REGULAR);
    }

    /**
     * Registers the `woocommerce_payment_gateways` hook with loadPaymentGateways as the callback.
     *
     * @throws Exception
     */
    private function addHooks()
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'loadHandlers'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_payment_gateways')
            ->setArgumentsCount(1)
            ->setHandler([$this, 'loadPaymentGateways'])
            ->execute();
    }

    /**
     * Broadcasts an event once the GDP gateway is active (available to be setup) for the first time.
     *
     * @throws Exception
     */
    protected static function maybeBroadcastPaymentGatewayFirstActiveEvent(AbstractPaymentGateway $gateway)
    {
        if (! Configuration::get('woocommerce.flags.broadcastGoDaddyPaymentsFirstActiveEvent')) {
            return;
        }

        Events::broadcast(new PaymentGatewayFirstActiveEvent($gateway->id));

        Configuration::set('woocommerce.flags.broadcastGoDaddyPaymentsFirstActiveEvent', false);

        update_option('gd_mwc_broadcast_go_daddy_payments_first_active', 'no');
    }

    /**
     * Gets a list of initialized core payment gateways.
     *
     * @return AbstractPaymentGateway[]
     * @throws Exception
     */
    public static function getPaymentGateways() : array
    {
        if (! empty(static::$paymentGateways)) {
            return static::$paymentGateways;
        }

        foreach (static::$paymentGatewayClasses as $class) {
            /** @var AbstractPaymentGateway|string $class */
            if (! is_callable($class.'::isActive') || ! $class::isActive()) {
                continue;
            }

            /** @var AbstractPaymentGateway $gateway */
            $gateway = new $class;

            static::$paymentGateways[$gateway->id] = $gateway;

            static::maybeBroadcastPaymentGatewayFirstActiveEvent($gateway);
        }

        return static::$paymentGateways;
    }

    /**
     * Determines whether a gateway is a platform managed payment gateway, by ID.
     *
     * @param string $gatewayId
     * @return bool
     * @throws Exception
     */
    public static function isManagedPaymentGateway(string $gatewayId) : bool
    {
        return ArrayHelper::has(static::getPaymentGateways(), $gatewayId);
    }

    /**
     * Gets an instance of a platform managed payment gateway, for a given ID.
     *
     * @param string $gatewayId
     * @return AbstractPaymentGateway|null
     * @throws Exception
     */
    public static function getManagedPaymentGatewayInstance(string $gatewayId)
    {
        $gateway = ArrayHelper::get(static::getPaymentGateways(), $gatewayId, '');

        if (empty($gateway)) {
            return null;
        }

        if (is_object($gateway)) {
            return $gateway;
        }

        return new $gateway();
    }

    /**
     * Gets a new instance of the Apple Pay gateway.
     *
     * @return ApplePayGateway
     */
    protected function getApplePayGatewayInstance() : ApplePayGateway
    {
        return new ApplePayGateway();
    }

    /**
     * Determines that the feature can be loaded if WooCommerce is available.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return WooCommerceRepository::isWooCommerceActive();
    }
}
