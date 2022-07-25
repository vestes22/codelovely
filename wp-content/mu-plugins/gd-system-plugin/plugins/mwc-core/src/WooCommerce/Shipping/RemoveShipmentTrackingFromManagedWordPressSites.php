<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionActivationFailedException;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;

/**
 * Utility class to remove Native Shipment Tracking data added by accident to Managed WordPress Sites.
 *
 * TODO: remove this class if Native Shipment Tracking becomes available to sites that are not using the Ecommerce plan {@wvega 2021-07-26}
 *
 * @since 2.9.1
 */
class RemoveShipmentTrackingFromManagedWordPressSites
{
    use IsConditionalFeatureTrait;

    /**
     * Constructor.
     *
     * We register registerHooks() as a handler for the init action instead of calling the method directly
     * to avoid polluting tests that end up instantiating this class with mocks for the internal behavior
     * of the registerHooks() method and its dependencies.
     *
     * @since 2.9.1
     */
    public function __construct()
    {
        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'registerHooks'])
            ->execute();
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
        return WooCommerceRepository::isWooCommerceActive() && ! ManagedWooCommerceRepository::hasEcommercePlan();
    }

    /**
     * Registers the hooks.
     *
     * @since 2.9.1
     *
     * @throws Exception
     */
    public function registerHooks()
    {
        // try to limit processing to document requests initiated by a merchant on the admin dashboard
        if (WordPressRepository::isAjax() || ! current_user_can('manage_woocommerce')) {
            return;
        }

        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeRemoveShipmentTrackingData'])
            ->execute();
    }

    /**
     * Removes Native Shipment Tracking data added by accident and re-activates the Shipment Tracking plugin.
     *
     * @since 2.9.1
     */
    public function maybeRemoveShipmentTrackingData()
    {
        if (! Configuration::get('woocommerce.flags.shouldRemoveShipmentTrackingFromManagedWordPressSites')) {
            return;
        }

        if ($this->shouldTryToActivateShipmentTrackingPlugin()) {
            try {
                $this->maybeActivateShipmentTrackingPlugin();
            } catch (ExtensionActivationFailedException $exception) {
                // return early and let the plugin try again on the next request
                return;
            }
        }

        $this->removeShipmentTrackingData();

        // switch the flag off: we only need to clean up data once
        Configuration::set('woocommerce.flags.shouldRemoveShipmentTrackingFromManagedWordPressSites', false);
        update_option('mwc_should_remove_shipment_tracking_from_managed_wordpress_sites', 'no');
    }

    /**
     * Determines whether we should try to activate the Shipment Tracking plugin.
     *
     * The mwc_show_shipment_tracking_plugin_deactivated_notice exists in the database only if the plugin was
     * deactivated when the Native Shipment Tracking feature became available.
     *
     * @return bool
     */
    protected function shouldTryToActivateShipmentTrackingPlugin() : bool
    {
        return 'yes' === get_option('mwc_show_shipment_tracking_plugin_deactivated_notice');
    }

    /**
     * Attempts to activate the Shipment Tracking plugin.
     *
     * @since 2.9.1
     *
     * @throws ExtensionActivationFailedException
     */
    protected function maybeActivateShipmentTrackingPlugin()
    {
        $plugin = ManagedExtensionsRepository::getInstalledManagedPlugin(
            'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php'
        );

        if ($plugin && ! $plugin->isActive()) {
            $plugin->activate();
        }
    }

    /**
     * Deletes Native Shipment Tracking options that were added accidentally.
     *
     * @since 2.9.1
     */
    protected function removeShipmentTrackingData()
    {
        delete_option('mwc_shipment_tracking_active');
        delete_option('mwc_should_deactivate_shipment_tracking_plugin');
        delete_option('mwc_show_shipment_tracking_plugin_deactivated_notice');
        delete_option('mwc_broadcast_shipment_tracking_feature_enabled_event');
    }
}
