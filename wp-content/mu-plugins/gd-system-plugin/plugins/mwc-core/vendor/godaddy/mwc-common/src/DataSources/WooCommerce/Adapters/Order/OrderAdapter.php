<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order;

use DateTime;
use DateTimezone;
use Exception;
use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CancelledOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CompletedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\FailedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\HeldOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\PendingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\ProcessingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\RefundedOrderStatus;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Order_Item_Tax;

/**
 * Order adapter.
 *
 * Converts between a native order object and a WooCommerce order object.
 *
 * @since 3.4.1
 */
class OrderAdapter implements DataSourceAdapterContract
{
    /** @var WC_Order WooCommerce order object */
    protected $source;

    /** @var string the order class name */
    protected $orderClass = Order::class;

    /** @var array order status relationships */
    protected $statuses = [
        'cancelled'  => CancelledOrderStatus::class,
        'completed'  => CompletedOrderStatus::class,
        'failed'     => FailedOrderStatus::class,
        'on-hold'    => HeldOrderStatus::class,
        'pending'    => PendingOrderStatus::class,
        'processing' => ProcessingOrderStatus::class,
        'refunded'   => RefundedOrderStatus::class,
    ];

    /**
     * Order adapter constructor.
     *
     * @since 3.4.1
     *
     * @param WC_Order $order WooCommerce order object.
     */
    public function __construct(WC_Order $order)
    {
        $this->source = $order;
    }

    /**
     * Converts a WooCommerce order object into a native order object.
     *
     * @since 3.4.1
     *
     * @return Order
     * @throws Exception
     */
    public function convertFromSource() : Order
    {
        $order = (new $this->orderClass())
            ->setId($this->source->get_id())
            ->setNumber($this->source->get_order_number());

        if ($status = $this->convertStatusFromSource()) {
            $order->setStatus($status);
        }

        // dates
        if ($createdAt = $this->source->get_date_created()) {
            $order->setCreatedAt(new DateTime($createdAt->format('c')));
        }
        if ($updatedAt = $this->source->get_date_modified()) {
            $order->setUpdatedAt(new DateTime($updatedAt->format('c')));
        }

        // customer data
        $order
            ->setCustomerId($this->source->get_customer_id())
            ->setCustomerIpAddress($this->source->get_customer_ip_address());

        // addresses
        $order
            ->setBillingAddress((new AddressAdapter($this->source->get_address('billing')))->convertFromSource())
            ->setShippingAddress((new AddressAdapter($this->source->get_address('shipping')))->convertFromSource());

        // order items
        $order = $this->convertOrderItemsFromSource($order);

        // order amounts
        $order->setLineAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_subtotal()));
        $order->setShippingAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_shipping_total()));
        $order->setFeeAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_fees()));
        $order->setTaxAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_tax()));
        $order->setTotalAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total()));

        // payment
        $order->setPaymentMethod($this->source->get_payment_method() ?? '');

        return $order;
    }

    /**
     * Converts the source's status to a normalized status object.
     *
     * @since 3.4.1
     *
     * @return OrderStatusContract|null
     */
    protected function convertStatusFromSource()
    {
        if (! $statusClass = ArrayHelper::get($this->statuses, $this->source->get_status())) {
            return null;
        }

        if (! class_exists($statusClass)) {
            return null;
        }

        return new $statusClass();
    }

    /**
     * Converts WooCommerce order items into native order items counterparts.
     *
     * @since 3.4.1
     *
     * @param Order $order
     * @return Order
     */
    private function convertOrderItemsFromSource(Order $order) : Order
    {
        $feeItems = $lineItems = $shippingItems = $taxItems = [];

        foreach (['fee', 'line_item', 'shipping', 'tax'] as $itemsKey) {
            foreach ($this->source->get_items($itemsKey) as $item) {
                switch (get_class($item)) {
                    case WC_Order_Item_Fee::class:
                        /* @var WC_Order_Item_Fee $item */
                        $feeItems[] = (new FeeItemAdapter($item))->convertFromSource();
                        break;
                    case WC_Order_Item_Product::class:
                        /* @var WC_Order_Item_Product $item */
                        $lineItems[] = (new LineItemAdapter($item))->convertFromSource();
                        break;
                    case WC_Order_Item_Shipping::class:
                        /* @var WC_Order_Item_Shipping $item */
                        $shippingItems[] = (new ShippingItemAdapter($item))->convertFromSource();
                        break;
                    case WC_Order_Item_Tax::class:
                        /* @var WC_Order_Item_Tax $item */
                        $taxItems[] = (new TaxItemAdapter($item))->convertFromSource();
                        break;
                }
            }
        }

        return $order
            ->setFeeItems($feeItems)
            ->setLineItems($lineItems)
            ->setShippingItems($shippingItems)
            ->setTaxItems($taxItems);
    }

    /**
     * Converts an order amount from source.
     *
     * @since 3.4.1
     *
     * @param float $amount
     * @return CurrencyAmount
     */
    private function convertCurrencyAmountFromSource(float $amount) : CurrencyAmount
    {
        return (new CurrencyAmountAdapter($amount, $this->source->get_currency()))->convertFromSource();
    }

    /**
     * Converts a native order object into a WooCommerce order object.
     *
     * @since 3.4.1
     *
     * @param Order|null $order native order object to convert
     * @return WC_Order WooCommerce order object
     * @throws Exception
     */
    public function convertToSource($order = null) : WC_Order
    {
        if (! $order instanceof Order) {
            return $this->source;
        }

        $this->source->set_id($order->getId());

        if ($status = $order->getStatus()) {
            $this->source->set_status($this->convertStatusToSource($status));
        }

        // dates
        if ($dateCreated = $order->getCreatedAt()) {
            $this->source->set_date_created($dateCreated->setTimezone(new DateTimeZone('UTC'))->getTimestamp());
        }
        if ($dateUpdated = $order->getUpdatedAt()) {
            $this->source->set_date_modified($dateUpdated->setTimezone(new DateTimeZone('UTC'))->getTimestamp());
        }

        // customer data
        $this->source->set_customer_id($order->getCustomerId());
        $this->source->set_customer_ip_address($order->getCustomerIpAddress());

        // addresses
        $this->source->set_address((new AddressAdapter([]))->convertToSource($order->getBillingAddress()), 'billing');
        $this->source->set_address((new AddressAdapter([]))->convertToSource($order->getShippingAddress()), 'shipping');

        // order items
        $this->convertOrderItemsToSource($order);

        // totals
        $this->source->calculate_totals();

        return $this->source;
    }

    /**
     * Converts native order items to WooCommerce order items.
     *
     * @since 3.4.1
     *
     * @param Order $order
     * @throws Exception
     */
    private function convertOrderItemsToSource(Order $order)
    {
        foreach ($order->getFeeItems() as $feeItem) {
            $this->source->add_item((new FeeItemAdapter($this->getWooCommerceOrderItemInstance(WC_Order_Item_Fee::class, $order->getId())))->convertToSource($feeItem));
        }

        foreach ($order->getLineItems() as $lineItem) {
            $this->source->add_item((new LineItemAdapter($this->getWooCommerceOrderItemInstance(WC_Order_Item_Product::class, $order->getId())))->convertToSource($lineItem));
        }

        foreach ($order->getShippingItems() as $shippingItem) {
            $this->source->add_item((new ShippingItemAdapter($this->getWooCommerceOrderItemInstance(WC_Order_Item_Shipping::class, $order->getId())))->convertToSource($shippingItem));
        }

        foreach ($order->getTaxItems() as $taxItem) {
            $this->source->add_item((new TaxItemAdapter($this->getWooCommerceOrderItemInstance(WC_Order_Item_Tax::class, $order->getId())))->convertToSource($taxItem));
        }
    }

    /**
     * Converts the given status object to the source status.
     *
     * @since 3.4.1
     *
     * @param OrderStatusContract $status
     *
     * @return string
     */
    protected function convertStatusToSource(OrderStatusContract $status) : string
    {
        $statusName = $status->getName();
        $statuses = array_flip($this->statuses);

        return $statuses[$statusName] ?? $statusName;
    }

    /**
     * Gets a WooCommerce order item instance of a given type.
     *
     * @internal
     *
     * @since 3.4.1
     *
     * @param string $itemClass class name of the item to instantiate
     * @param int $orderId associated Order ID
     *
     * @return WC_Order_Item|WC_Order_Item_Fee|WC_Order_Item_Product|WC_Order_Item_Shipping|WC_Order_Item_Tax
     */
    public function getWooCommerceOrderItemInstance(string $itemClass, int $orderId) : WC_Order_Item
    {
        $item = new $itemClass();
        if (method_exists($item, 'set_order_id')) {
            $item->set_order_id($orderId);
        }

        return $item;
    }
}
