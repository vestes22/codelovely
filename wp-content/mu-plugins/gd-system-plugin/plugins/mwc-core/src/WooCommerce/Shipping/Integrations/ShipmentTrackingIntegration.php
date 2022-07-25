<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\Integrations;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionDeactivationFailedException;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Core\Events\FeatureDisabledEvent;
use GoDaddy\WordPress\MWC\Core\Events\PluginActivatedEvent;
use GoDaddy\WordPress\MWC\Core\Events\PluginDeactivatedEvent;

/** Integration class for the Shipment Tracking plugin */
class ShipmentTrackingIntegration
{
    /** @var PluginExtension|null plugin extension instance for the Shipment Tracking plugin */
    protected $plugin;

    /**
     * ShipmentTrackingIntegration constructor.
     *
     * @since 2.10.0
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Adds the hooks.
     *
     * @since 2.10.0
     */
    protected function addHooks()
    {
        // try to limit processing to document requests initiated by a merchant on the admin dashboard
        if (is_ajax() || ! current_user_can('manage_woocommerce')) {
            return;
        }

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeDeactivateShipmentTrackingPlugin'])
            ->execute();

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeDeactivateShipmentTrackingFeature'])
            ->execute();
    }

    /**
     * Deactivates the shipment tracking plugin if certain conditions are met.
     *
     * @since 2.10.0
     */
    public function maybeDeactivateShipmentTrackingPlugin()
    {
        if ($this->isShippingEnabled() && Configuration::get('woocommerce.flags.shouldDeactivateShipmentTrackingPlugin')) {
            if ($this->isShipmentTrackingPluginActive()) {
                try {
                    $this->getPlugin()->deactivate();
                } catch (ExtensionDeactivationFailedException $exception) {
                    // return early and let the plugin try again on the next request
                    return;
                }

                Configuration::set('woocommerce.flags.showShipmentTrackingPluginDeactivatedNotice', true);

                Events::broadcast(new PluginDeactivatedEvent($this->getPlugin()));

                update_option('mwc_show_shipment_tracking_plugin_deactivated_notice', 'yes');
            }

            // switch the flag off: the plugin was not active when the native feature became available
            // so we should deactivate the native feature if the plugin becomes active at some point
            Configuration::set('woocommerce.flags.shouldDeactivateShipmentTrackingPlugin', false);
            update_option('mwc_should_deactivate_shipment_tracking_plugin', 'no');
        }
    }

    /**
     * Determines whether WooCommerce shipping is enabled.
     *
     * TODO: move this method to the WooCommerce repository in mwc-common {wvega 2021-06-17}
     *
     * @since 2.10.0
     *
     * @return bool
     */
    protected function isShippingEnabled() : bool
    {
        return (bool) wc_shipping_enabled();
    }

    /**
     * Determines whether the Shipment Tracking plugin is currently installed and active.
     *
     * @since 2.10.0
     *
     * @return bool
     */
    protected function isShipmentTrackingPluginActive() : bool
    {
        return $this->getPlugin() && $this->getPlugin()->isActive();
    }

    /**
     * Deactivates Shipment tracking if certain conditions are met.
     *
     * @since 2.10.0
     */
    public function maybeDeactivateShipmentTrackingFeature()
    {
        if ($this->isShippingEnabled() &&
            ! Configuration::get('woocommerce.flags.shouldDeactivateShipmentTrackingPlugin') &&
            $this->isShipmentTrackingPluginActive()) {
            Configuration::set('features.shipment_tracking.enabled', false);
            update_option('mwc_shipment_tracking_active', 'no');

            Events::broadcast(new PluginActivatedEvent($this->getPlugin()));
            Events::broadcast(new FeatureDisabledEvent('shipment_tracking'));
        }
    }

    /**
     * Looks for the installed managed Shipment Tracking plugin and memoizes the result.
     * If plugin is not found, returns null.
     *
     * @since 2.10.0
     *
     * @return PluginExtension|null
     * @throws \Exception
     */
    protected function getPlugin()
    {
        if ($this->plugin) {
            return $this->plugin;
        }

        $this->plugin = ManagedExtensionsRepository::getInstalledManagedPlugin(
            'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php'
        );

        return $this->plugin;
    }
}
