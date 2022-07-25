<?php

use GoDaddy\WordPress\MWC\Core\Email\Events\Subscribers\EmailNotificationsSettingsUpdatedSubscriber;
use GoDaddy\WordPress\MWC\Core\Events\Subscribers\EventBridgeSubscriber;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\PoyntOrderPushSubscriber;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\RegisterWebhooksSubscriber;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\WebhookSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\ApplePayEnabledSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\CaptureTransactionOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\CompleteSetUpPaymentsTaskSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\PaymentTransactionOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\RefundTransactionOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\RequestDebugNoticeSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\RequestLogSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\ResponseDebugNoticeSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\ResponseLogSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers\VoidTransactionOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers\ShipmentEventsSubscriber;

return [
    /*
     *--------------------------------------------------------------------------
     * General Settings
     *--------------------------------------------------------------------------
     *
     * The following are general settings needed for the operation and use of the overall
     * event system
     */
    'auth' => [
        'type'  => 'Bearer',
        'token' => defined('MWC_EVENTS_AUTH_TOKEN') ? MWC_EVENTS_AUTH_TOKEN : 'eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJhcGktZXZlbnRzLm13Yy5zZWN1cmVzZXJ2ZXIubmV0Iiwic2NvcGUiOiJ3cml0ZSIsImlhdCI6MTYxNzMwNDUwOSwiZXhwIjoxNjI1MDgwNTA5LCJpc3MiOiJhcGktZXZlbnRzLWF1dGgifQ.9CQuWuykArqzbFFXg0IbIwSJ9cKs2VzvqjjPLya7UktKEx9HnYNgcPnB5FTHbEY2aUc4yz9UBkYfJgRiiD5dfA',
    ],

    'send_local_events' => defined('MWC_SEND_LOCAL_EVENTS') ? MWC_SEND_LOCAL_EVENTS : false,

    /*
     *--------------------------------------------------------------------------
     * Event Listeners / Subscribers
     *--------------------------------------------------------------------------
     *
     * The following array contains events and a list of their subscribers.  In order
     * to have a cached subscriber for a given event at optimal performance, the
     * subscriber should be listed under the events key below.
     *
     * Event with Namespace => subscriber class
     *
     * All subscribers will receive the full event object by default.  Determination
     * of if the event is queued before triggering the listener should/is done
     * via declaration on the Event itself.
     *
     */
    'listeners' => [
        GoDaddy\WordPress\MWC\Core\Events\OrderTrackingInformationCreatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\OrderTrackingInformationUpdatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\PageViewEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\ProductCreatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\ProductUpdatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\ShippingZoneMethodAddedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\ButtonClickedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\FeatureEnabledEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\FeatureDisabledEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\PluginActivatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\PluginDeactivatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\SiteHeartbeatEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\SettingsUpdatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayConnectedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayEnabledEvent::class => [
            EventBridgeSubscriber::class,
            CompleteSetUpPaymentsTaskSubscriber::class,
            ApplePayEnabledSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayDisabledEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayFirstActiveEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentSettingsPageViewEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentTransactionCreatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\ShipmentCreatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\ShipmentUpdatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\ShipmentDeletedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\CaptureTransactionEvent::class => [
            CaptureTransactionOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\PaymentTransactionEvent::class => [
            PaymentTransactionOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\BeforeCreateRefundEvent::class => [
            GoDaddyPaymentsGateway::class,
        ],
        GoDaddy\WordPress\MWC\Core\Events\BeforeCreateVoidEvent::class => [
            GoDaddyPaymentsGateway::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\RefundTransactionEvent::class => [
            RefundTransactionOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\VoidTransactionEvent::class => [
            VoidTransactionOrderNotesSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\ProviderRequestEvent::class => [
            RequestDebugNoticeSubscriber::class,
            RequestLogSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Payments\Events\ProviderResponseEvent::class => [
            ResponseDebugNoticeSubscriber::class,
            ResponseLogSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Shipping\Events\ShipmentCreatedEvent::class => [
            ShipmentEventsSubscriber::class,
            GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers\EventBridgeShipmentEventsSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Shipping\Events\ShipmentUpdatedEvent::class => [
            ShipmentEventsSubscriber::class,
            GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers\EventBridgeShipmentEventsSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Shipping\Events\ShipmentDeletedEvent::class => [
            GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking\Events\Subscribers\EventBridgeShipmentEventsSubscriber::class,
        ],
        GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\Events\ProfitReportsPageViewEvent::class => [
            EventBridgeSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Core\Events\OrderCreatedEvent::class => [
            EventBridgeSubscriber::class,
            PoyntOrderPushSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Core\Events\OrderUpdatedEvent::class => [
            EventBridgeSubscriber::class,
            \GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\OrderUpdatedSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Core\Features\GoogleAnalytics\Events\GoogleAnalyticsConnectedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\AccountUpdatedEvent::class => [
            RegisterWebhooksSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\WebhookReceivedEvent::class => [
            WebhookSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events\EmailNotificationsSettingsUpdatedEvent::class => [
            EventBridgeSubscriber::class,
            EmailNotificationsSettingsUpdatedSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Core\Features\Onboarding\Events\OnboardingSettingUpdatedEvent::class => [
            EventBridgeSubscriber::class,
        ],
        \GoDaddy\WordPress\MWC\Common\Events\ModelEvent::class => [
            EventBridgeSubscriber::class,
        ],
    ],

    /*
     *--------------------------------------------------------------------------
     * Event Producers
     *--------------------------------------------------------------------------
     *
     * The following array contains event producers that will be instantiated when
     * the package loads and are expected to broadcast events when the appropriate
     * action occurs.
     *
     * Please use the fully qualified namespace of the producer to avoid creating a long
     * list of use statements at the top of this file and allow to easily identify
     * the location of a given producer class within the application structure or its
     * dependencies.
     *
     * Use
     *
     * GoDaddy\WordPress\MWC\Core\Events\Producers\ProductEventsProducer::class
     *
     * instead of
     *
     * ProductEventsProducer::class
     */
    'producers' => [
        GoDaddy\WordPress\MWC\Core\Events\Producers\OrderTrackingEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\PageEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\ProductEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\ShippingZoneMethodEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\SiteHeartbeatEventProducer::class,
        GoDaddy\WordPress\MWC\Core\Sync\Events\Producers\PullEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\WebhookEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Events\Producers\OrderEventsProducer::class,
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers\PushOrdersProducer::class,
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers\PushTransactionsProducer::class,
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers\RegisterWebhooksProducer::class,
        GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers\WebhookSubscriber::class,
    ],
];
