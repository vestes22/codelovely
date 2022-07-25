<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\AbstractShipmentsTable;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\HtmlEmailShipmentsTable;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\OrderShipmentsTable;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\PlainEmailShipmentsTable;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\ShipmentTracking\OrderFulfillmentDataStore;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;
use WC_Email;
use WC_Order;

class ShipmentsTable
{
    /**
     * ShipmentsTable constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Adds the hooks to render shipment tables.
     *
     * @since x.y.x
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::action()
            ->setGroup('woocommerce_order_details_after_order_table')
            ->setHandler([$this, 'renderOrderShipmentsTable'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_email_after_order_table')
            ->setHandler([$this, 'maybeRenderEmailShipmentsTable'])
            ->setArgumentsCount(4)
            ->execute();
    }

    /**
     * Renders the order shipments table.
     *
     * @since 2.10.0
     * @internal
     */
    public function renderOrderShipmentsTable($wcOrder)
    {
        if (! is_a($wcOrder, 'WC_Order')) {
            return;
        }

        if (! $orderFulfillment = $this->getOrderFulfillment($wcOrder)) {
            return;
        }

        $this->getShipmentsTable($orderFulfillment, OrderShipmentsTable::class)->render();
    }

    /**
     * Possibly renders the email shipments table.
     *
     * @since 2.10.0
     * @internal
     *
     * @param WC_Order $order order instance
     * @param bool $sentToAdmin whether or not this email will be sent to admin
     * @param bool $isPlainText whether this is a plain text email
     * @param WC_Email $email the email instance
     */
    public function maybeRenderEmailShipmentsTable($order, $sentToAdmin, $isPlainText, $email)
    {
        if (! $this->shouldRenderEmailShipmentsTable($order, $email)) {
            return;
        }

        if (! $orderFulfillment = $this->getOrderFulfillment($order)) {
            return;
        }

        $this->getShipmentsTable(
            $orderFulfillment,
            $isPlainText ? PlainEmailShipmentsTable::class : HtmlEmailShipmentsTable::class
        )->render();
    }

    /**
     * Determines whether to render the email shipments table.
     *
     * @since 2.10.0
     *
     * @param WC_Order $order
     * @param WC_Email $email the email instance
     *
     * @return bool
     */
    protected function shouldRenderEmailShipmentsTable(WC_Order $order, $email) : bool
    {
        if (is_a($email, 'WC_Email_Customer_Refunded_Order')) {
            return false;
        }

        return $order->get_type() !== 'shop_order_refund' && ! $order->has_status('refunded');
    }

    /**
     * Gets an Order Fulfillment object for the given order.
     *
     * @since 2.10.0
     *
     * @param WC_Order $wcOrder WooCommerce Order object
     *
     * @return OrderFulfillment|null
     */
    protected function getOrderFulfillment(WC_Order $wcOrder)
    {
        return (new OrderFulfillmentDataStore())->read($wcOrder->get_id());
    }

    /**
     * Gets a new instance of the given shipments table class.
     *
     * @since 2.10.0
     * @param string $class
     * @return AbstractShipmentsTable
     */
    protected function getShipmentsTable(OrderFulfillment $orderFulfillment, string $class) : AbstractShipmentsTable
    {
        return new $class($orderFulfillment);
    }
}
