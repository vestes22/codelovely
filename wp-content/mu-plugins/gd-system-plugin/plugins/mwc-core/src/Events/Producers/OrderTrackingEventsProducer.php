<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Events\AbstractOrderTrackingInformationEvent;
use GoDaddy\WordPress\MWC\Core\Events\OrderTrackingInformationCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Events\OrderTrackingInformationUpdatedEvent;

class OrderTrackingEventsProducer implements ProducerContract
{
    /** @var Register[] registered hooks */
    protected $hooks = [];

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
            ->setGroup('woocommerce_process_shop_order_meta')
            ->setHandler([$this, 'addHooks'])
            ->setPriority(-10)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_process_shop_order_meta')
            ->setHandler([$this, 'removeHooks'])
            ->setPriority(10)
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_wc_shipment_tracking_save_form')
            ->setHandler([$this, 'addHooks'])
            ->setPriority(0)
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_wc_shipment_tracking_save_form')
            ->setHandler([$this, 'removeHooks'])
            ->setPriority(20)
            ->execute();
    }

    /**
     * Adds the hooks to possibly fire order tracking information events.
     *
     * @return void
     * @throws Exception
     */
    public function addHooks()
    {
        $this->hooks[] = Register::action()
            ->setGroup('added_post_meta')
            ->setHandler([$this, 'maybeFireOrderTrackingInformationCreatedEvent'])
            ->setPriority(20)
            ->setArgumentsCount(4);

        $this->hooks[] = Register::action()
            ->setGroup('updated_post_meta')
            ->setHandler([$this, 'maybeFireOrderTrackingInformationUpdatedEvent'])
            ->setPriority(20)
            ->setArgumentsCount(4);

        foreach ($this->hooks as $hook) {
            $hook->execute();
        }
    }

    /**
     * Removes any hooks added by this producer.
     *
     * @return void
     */
    public function removeHooks()
    {
        foreach ($this->hooks as $hook) {
            $hook->deregister();
        }
    }

    /**
     * Fires the order tracking information created event if the tracking items meta is being created.
     *
     * @param $metaId
     * @param $objectId
     * @param $metaKey
     * @param $metaValue
     *
     * @return void
     * @throws Exception
     */
    public function maybeFireOrderTrackingInformationCreatedEvent($metaId, $objectId, $metaKey, $metaValue)
    {
        if ($metaKey !== '_wc_shipment_tracking_items' || ! $objectId) {
            return;
        }

        $this->fireOrderTrackingInformationEvent(new OrderTrackingInformationCreatedEvent(), (int) $objectId, $metaValue);
    }

    /**
     * Fires the order tracking information updated event if the tracking items meta is being updated.
     *
     * @param $metaId
     * @param $objectId
     * @param $metaKey
     * @param $metaValue
     *
     * @return void
     * @throws Exception
     */
    public function maybeFireOrderTrackingInformationUpdatedEvent($metaId, $objectId, $metaKey, $metaValue)
    {
        if ($metaKey !== '_wc_shipment_tracking_items' || ! $objectId) {
            return;
        }

        $this->fireOrderTrackingInformationEvent(new OrderTrackingInformationUpdatedEvent(), (int) $objectId, $metaValue);
    }

    /**
     * Fires an order tracking information event.
     *
     * @param AbstractOrderTrackingInformationEvent $event
     * @param int $orderId
     * @param array $trackingItems
     *
     * @return void
     * @throws Exception
     */
    protected function fireOrderTrackingInformationEvent(AbstractOrderTrackingInformationEvent $event, int $orderId, array $trackingItems)
    {
        $event->setWooCommerceOrder(OrdersRepository::get($orderId));
        $event->setTrackingItems($trackingItems);

        Events::broadcast($event);
    }
}
