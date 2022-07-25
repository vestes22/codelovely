<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\LocalDelivery\LocalDelivery;

/**
 * Core payment gateways.
 *
 * Takes care of the necessary tasks for adding the shipping method(s) in a way that WooCommerce understands.
 *
 * @since 2.14.0
 */
class CoreShippingMethods
{
    use IsConditionalFeatureTrait;

    /**
     * @var string[] shipping methods to load
     *
     * @since 2.14.0
     * */
    protected static $shippingMethodClasses = [
        'mwc_local_delivery' => LocalDelivery::class,
    ];

    /**
     * Shipment constructor.
     *
     * @since 2.14.0
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Determines whether this feature should be loaded.
     *
     * @since x.y.z
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature(): bool
    {
        // should not display if Bopit functionality is disabled through configurations
        if (! self::isActive()) {
            return false;
        }

        return WooCommerceRepository::isWooCommerceActive() && wc_shipping_enabled();
    }

    /**
     * Checks if Bopit feature flag is set.
     *
     * @since x.y.z
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive(): bool
    {
        return Configuration::get('features.bopit', false);
    }

    /**
     * Adds the hooks.
     *
     * @since 2.14.0
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_shipping_methods')
            ->setHandler([$this, 'addShippingMethods'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_get_order_item_totals')
            ->setHandler([$this, 'addLocalDeliveryInstruction'])
            ->setArgumentsCount(2)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_after_shipping_rate')
            ->setHandler([$this, 'addShippingDescription'])
            ->execute();
    }

    /**
     * Add new shipping method.
     *
     * @since 2.14.0
     *
     * @param array $shippingMethods
     * @return array $shippingMethods
     */
    public function addShippingMethods($shippingMethods)
    {
        if (! ArrayHelper::accessible($shippingMethods)) {
            return $shippingMethods;
        }
        // add our gateways to the top of the list
        foreach (static::$shippingMethodClasses as $key => $shippingMethod) {
            $shippingMethods[$key] = $shippingMethod;
        }

        return $shippingMethods;
    }

    /**
     * Add description under the title on cart and checkout.
     *
     * @since 2.14.0
     *
     * @param object $method
     * @return void
     */
    public function addShippingDescription($method)
    {
        if ('mwc_local_delivery' === $method->method_id) {
            $shippingInstance = $this->getShippingInstance($method->method_id, $method->instance_id);
            $checkoutDescription = $shippingInstance->get_option('checkout_description');

            if (! empty($checkoutDescription)) {
                echo sprintf('<p class="mwc-local-delivery-desc">%1$s</p>', __($checkoutDescription, 'mwc-core'));
            }
        }
    }

    /**
     * Add order received instruction if local delivery.
     *
     * @param array $rows order details items.
     * @param object $order
     * @return array $rows
     * @throws Exception
     * @since 2.14.0
     */
    public function addLocalDeliveryInstruction($rows, $order)
    {
        // @NOTE: Need to bail if Woo isn't active or will fatal on using shipping object and methods {JO: 2021-09-16}
        if (WooCommerceRepository::isWooCommerceActive()) {
            foreach ($order->get_shipping_methods() as $shippingMethod) {
                if ('mwc_local_delivery' === $shippingMethod->get_method_id()) {
                    $shippingInstance = $this->getShippingInstance($shippingMethod->get_method_id(), (int) $shippingMethod->get_instance_id());
                    $orderReceivedInstruction = $shippingInstance->get_option('order_received_instruction');

                    if (! empty($orderReceivedInstruction)) {
                        $rows = ArrayHelper::insert($rows, ['order_received_instruction' => [
                            'label' => __('Order Instructions:', 'mwc-core'),
                            'value' => $orderReceivedInstruction,
                        ]], 'shipping');
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Get shipping method instance.
     *
     * @since 2.14.0
     *
     * @param string $method_id
     * @param int $instance_id
     * @return \WC_Shipping_Method
     */
    public function getShippingInstance(string $methodId, int $instanceId): \WC_Shipping_Method
    {
        $shippingClassNames = WC()->shipping->get_shipping_method_class_names();

        return new $shippingClassNames[$methodId]($instanceId);
    }
}
