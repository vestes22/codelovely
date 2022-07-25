<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use GoDaddy\WordPress\MWC\Core\Events\FeatureEnabledEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Pages\EditOrder\ShipmentTrackingMetabox;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Pages\Orders\Columns\ShipmentTrackingColumn;
use GoDaddy\WordPress\MWC\Core\WooCommerce\ShipmentsTable;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\Integrations\ShipmentTrackingIntegration;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Emails;

/**
 * Shipment tracking handler.
 *
 * @since 2.10.0
 */
class ShipmentTracking
{
    use IsConditionalFeatureTrait;

    /**
     * ShipmentTracking constructor.
     *
     * @since 2.10.0
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Determines whether the feature can be loaded.
     *
     * @since 2.9.1
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return WooCommerceRepository::isWooCommerceActive() && ManagedWooCommerceRepository::hasEcommercePlan();
    }

    /**
     * Determines whether shipment tracking is active.
     *
     * @since 2.10.0
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive() : bool
    {
        return is_callable('wc_shipping_enabled')
            && wc_shipping_enabled()
            && Configuration::get('features.shipment_tracking.enabled');
    }

    /**
     * Adds the hooks.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'init'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeBroadcastFeatureEnabledEvent'])
            ->execute();
    }

    /**
     * Initializes the Shipment Tracking feature if is currently available.
     *
     * We can't call self::isActive() in the constructor, because WooCommerce may not be loaded
     * when this class is instantiated.
     *
     * @internal
     *
     * @since 2.10.0
     */
    public function init()
    {
        if (! self::isActive()) {
            return;
        }

        new Emails();
        new ShipmentsTable();
        new ShipmentTrackingColumn();
        new ShipmentTrackingMetabox();
        new ShipmentTrackingIntegration();
    }

    /**
     * Broadcasts a FeatureEnabledEvent when the shipment tracking feature is enabled for the first time.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function maybeBroadcastFeatureEnabledEvent()
    {
        if (! $this->shouldBroadcastFeatureEnabledEvent()) {
            return;
        }

        Events::broadcast(new FeatureEnabledEvent('shipment_tracking'));

        Configuration::set('woocommerce.flags.broadcastShipmentTrackingFeatureEnabledEvent', false);

        update_option('mwc_broadcast_shipment_tracking_feature_enabled_event', 'no');
    }

    /**
     * Determines whether it should broadcast a FeatureEnabledEvent for the shipment tracking feature.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    protected function shouldBroadcastFeatureEnabledEvent()
    {
        // try to limit processing to document requests initiated by a merchant on the admin dashboard
        if (is_ajax() || ! current_user_can('manage_woocommerce')) {
            return false;
        }

        if (! Configuration::get('woocommerce.flags.broadcastShipmentTrackingFeatureEnabledEvent') || ! self::isActive()) {
            return false;
        }

        return true;
    }
}
