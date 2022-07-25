<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Events\ShippingZoneMethodAddedEvent;
use WC_Shipping_Zones;

class ShippingZoneMethodEventsProducer implements ProducerContract
{
    /**
     * Sets up the Coupon events producer.
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
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('woocommerce_shipping_zone_method_added')
            ->setHandler([$this, 'fireShippingZoneMethodAddedEvent'])
            ->setArgumentsCount(3)
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeFireLocalPickupShippingMethodAddedEvent'])
            ->execute();
    }

    /**
     * Fires the shipping zone method added event when the site is using the native local pickup shipping method in at least one shipping zone.
     *
     * @internal
     *
     * @throws Exception
     */
    public function maybeFireLocalPickupShippingMethodAddedEvent()
    {
        // bail early if event already fired.
        if ('yes' !== Configuration::get('woocommerce.flags.maybeFireLocalPickupShippingMethodAddedEvent')) {
            return;
        }

        $shippingZones = WC_Shipping_Zones::get_zones();

        // bail early if not shipping zones setup ye.
        if (empty($shippingZones)) {
            return;
        }

        foreach ($shippingZones as $zone) {
            // search for Local Pickup Plus shipping method
            $localPickupShippingMethods = ArrayHelper::where(ArrayHelper::get($zone, 'shipping_methods', []), static function ($method) {
                return 'local_pickup' === $method->id;
            });

            if ($localPickupShippingMethods) {
                // fire event
                Events::broadcast(new ShippingZoneMethodAddedEvent((int) ArrayHelper::get($zone, 'id'), 'local_pickup'));

                // disable event fire flag
                update_option('gd_mwc_maybe_fire_local_pickup_shipping_method_added_event', 'no');
                Configuration::set('woocommerce.flags.maybeFireLocalPickupShippingMethodAddedEvent', 'no');

                // break out
                break;
            }
        }
    }

    /**
     * Fires the shipping zone method added event.
     *
     * @param $instanceId
     * @param $type
     * @param $shippingZoneId
     *
     * @throws Exception
     */
    public function fireShippingZoneMethodAddedEvent($instanceId, $type, $shippingZoneId)
    {
        if (! $type || ! $shippingZoneId) {
            return;
        }

        Events::broadcast(new ShippingZoneMethodAddedEvent((int) $shippingZoneId, (string) $type));
    }
}
