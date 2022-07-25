<?php

return [
    /*
     *--------------------------------------------------------------------------
     * Configuration Flags
     *--------------------------------------------------------------------------
     *
     * disable marketplace suggestions flag, string.
     *
     */
    'flags' => [
        'broadcastFirstGoDaddyPaymentsPaymentTransactionEvent'  => get_option('gd_mwc_broadcast_first_godaddy_payments_payment_transaction_event', 'yes'),
        'broadcastGoDaddyPaymentsFirstActiveEvent'              => get_option('gd_mwc_broadcast_go_daddy_payments_first_active', 'yes') === 'yes',
        'disableMarketplaceSuggestions'                         => get_option('gd_mwc_disable_woocommerce_marketplace_suggestions', 'yes'),
        'maybeFireLocalPickupShippingMethodAddedEvent'          => get_option('gd_mwc_maybe_fire_local_pickup_shipping_method_added_event', 'yes'),
        'shouldDeactivateShipmentTrackingPlugin'                => get_option('mwc_should_deactivate_shipment_tracking_plugin', 'yes') === 'yes',
        'showShipmentTrackingPluginDeactivatedNotice'           => get_option('mwc_show_shipment_tracking_plugin_deactivated_notice') === 'yes',
        'broadcastShipmentTrackingFeatureEnabledEvent'          => get_option('mwc_broadcast_shipment_tracking_feature_enabled_event', 'yes') === 'yes',
        'shouldRemoveShipmentTrackingFromManagedWordPressSites' => get_option('mwc_should_remove_shipment_tracking_from_managed_wordpress_sites', 'yes') === 'yes',
        'broadcastSiteHeartbeatEvent'                           => ((int) get_option('mwc_site_heartbeat_event_sent_at')) < 1628121600, // {value} < August 5, 2021 00:00:00
    ],

    /*
     *--------------------------------------------------------------------------
     * Order Item meta that should be hidden
     *--------------------------------------------------------------------------
     */
    'hiddenOrderItemMeta' => [
        '_mwc_fulfillment_status',
    ],
];
