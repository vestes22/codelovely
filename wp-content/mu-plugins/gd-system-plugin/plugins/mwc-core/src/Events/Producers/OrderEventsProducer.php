<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Events\OrderCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Events\OrderUpdatedEvent;
use WC_Order;

class OrderEventsProducer implements ProducerContract
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
            ->setGroup('woocommerce_checkout_order_created')
            ->setHandler([$this, 'maybeFireOrderCreatedEventFromCheckout'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_new_order')
            ->setHandler([$this, 'maybeFireOrderCreatedEvent'])
            ->setArgumentsCount(2)
            ->execute();

        Register::action()
            ->setGroup('woocommerce_update_order')
            ->setHandler([$this, 'maybeFireOrderUpdatedEvent'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Fires order created event via checkout process.
     *
     * @internal
     *
     * @param $order
     * @throws Exception
     */
    public function maybeFireOrderCreatedEventFromCheckout($order)
    {
        Events::broadcast((new OrderCreatedEvent())->setWooCommerceOrder($order));
    }

    /**
     * Fires order created event.
     *
     * @internal
     *
     * @param $orderId
     * @param $order
     * @throws Exception
     */
    public function maybeFireOrderCreatedEvent($orderId, $order = null)
    {
        if ($order = $this->getOrderForEventBroadcast($orderId, $order)) {
            Events::broadcast((new OrderCreatedEvent())->setWooCommerceOrder($order));
        }
    }

    /**
     * Fires order updated event.
     *
     * @internal
     *
     * @param $orderId
     * @param $order
     * @throws Exception
     */
    public function maybeFireOrderUpdatedEvent($orderId, $order = null)
    {
        if ($order = $this->getOrderForEventBroadcast($orderId, $order)) {
            Events::broadcast((new OrderUpdatedEvent())->setWooCommerceOrder($order));
        }
    }

    /**
     * Gets an order object for broadcast.
     *
     * @param int|mixed $orderId
     * @param WC_Order|mixed|null $order
     * @return WC_Order|null
     */
    private function getOrderForEventBroadcast($orderId, $order)
    {
        if (! $order instanceof WC_Order) {
            $order = is_numeric($orderId) ? OrdersRepository::get((int) $orderId) : null;
        }

        return $order instanceof WC_Order ? $order : null;
    }
}
