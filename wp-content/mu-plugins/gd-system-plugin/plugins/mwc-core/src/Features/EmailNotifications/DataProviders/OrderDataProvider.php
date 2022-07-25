<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\OrderAdapter;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\ProcessingOrderStatus;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DateTimeRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\OrderEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use WC_Data_Exception;
use WC_Order;
use WC_Order_Item_Product;

/**
 * The order data provider for email notifications with order-related placeholders.
 */
class OrderDataProvider implements DataProviderContract
{
    /** @var OrderEmailNotificationContract */
    protected $emailNotification;

    /**
     * Constructor.
     *
     * @param OrderEmailNotificationContract $emailNotification
     */
    public function __construct(OrderEmailNotificationContract $emailNotification)
    {
        $this->emailNotification = $emailNotification;
    }

    /**
     * Gets the data.
     *
     * @return array
     * @throws Exception
     */
    public function getData() : array
    {
        if (! $order = $this->getOrder()) {
            return [];
        }

        return $this->getOrderData($order);
    }

    /**
     * Gets the order.
     *
     * @return Order|null
     */
    protected function getOrder()
    {
        return $this->emailNotification->getOrder();
    }

    /**
     * Gets the data for the given order.
     *
     * @param Order $order
     * @return array
     * @throws Exception
     */
    protected function getOrderData(Order $order) : array
    {
        $billingAddress = $order->getBillingAddress();
        $createdAt = $order->getCreatedAt();

        return [
            'order_number'            => $order->getNumber(),
            'order_date'              => $createdAt ? $this->getFormattedDateTime($createdAt) : '',
            'order_billing_full_name' => trim($billingAddress->getFirstName().' '.$billingAddress->getLastName()),
        ];
    }

    /**
     * Gets an order adapter instance.
     *
     * @return OrderAdapter
     */
    protected function getOrderAdapter() : OrderAdapter
    {
        return new OrderAdapter(new WC_Order());
    }

    /**
     * Gets a WooCommerce order for the given order.
     *
     * @param Order $order
     * @return WC_Order
     * @throws Exception
     */
    protected function getWooCommerceOrder(Order $order) : WC_Order
    {
        return $order->getId() ? OrdersRepository::get($order->getId()) : $this->getPreviewWooCommerceOrder();
    }

    /**
     * Gets a dummy WooCommerce order for generating preview data.
     *
     * @return WC_Order
     * @throws WC_Data_Exception
     */
    protected function getPreviewWooCommerceOrder() : WC_Order
    {
        $wcOrder = new WC_Order();
        $wcOrder->set_id(101);
        $wcOrder->set_status('processing');
        $wcOrder->set_date_created(current_time('timestamp'));
        $wcOrder->set_customer_ip_address('192.168.0.1');

        $wcOrder->set_billing_address_1('Main Avenue 1');
        $wcOrder->set_billing_city('Springfield');
        $wcOrder->set_billing_state('MA');
        $wcOrder->set_billing_postcode('1234');
        $wcOrder->set_billing_country('US');
        $wcOrder->set_billing_company('John Doe Co.');
        $wcOrder->set_billing_first_name('John');
        $wcOrder->set_billing_last_name('Doe');

        $wcOrder->set_shipping_address_1('Main Avenue 1');
        $wcOrder->set_shipping_city('Springfield');
        $wcOrder->set_shipping_state('MA');
        $wcOrder->set_shipping_postcode('1234');
        $wcOrder->set_shipping_country('US');
        $wcOrder->set_shipping_company('John Doe Co.');
        $wcOrder->set_shipping_first_name('John');
        $wcOrder->set_shipping_last_name('Doe');

        $wcLineItem1 = new WC_Order_Item_Product();
        $wcLineItem1->set_id(0);
        $wcLineItem1->set_name('Product A');
        $wcLineItem1->set_quantity(1);
        $wcLineItem1->set_total(0.1);
        $wcLineItem1->set_total_tax(0.01);
        $wcOrder->add_item($wcLineItem1);

        $wcLineItem2 = new WC_Order_Item_Product();
        $wcLineItem2->set_id(0);
        $wcLineItem2->set_name('Product B');
        $wcLineItem2->set_quantity(1);
        $wcLineItem2->set_total(0.2);
        $wcLineItem2->set_total_tax(0.02);
        $wcOrder->add_item($wcLineItem2);

        $wcOrder->set_total(0.3);

        return $wcOrder;
    }

    /**
     * Gets a dummy order for generating preview data.
     *
     * @return Order
     */
    protected function getPreviewOrder() : Order
    {
        $billingAddress = (new Address())
            ->setFirstname('John')
            ->setLastName('Doe')
            ->setBusinessName('John Doe Co.')
            ->setCountryCode('US')
            ->setPostalCode('1234')
            ->setAdministrativeDistricts(['MA'])
            ->setLocality('Springfield')
            ->setLines(['Main Avenue 1']);
        $shippingAddress = clone $billingAddress;
        $lineItems = [
            (new LineItem())
                ->setId(0)
                ->setName('product-a')
                ->setLabel('Product A')
                ->setQuantity(1)
                ->setNeedsShipping(true)
                ->setTotalAmount(
                    (new CurrencyAmount())
                        ->setAmount(10)
                        ->setCurrencyCode('USD'))
                ->setTaxAmount(
                    (new CurrencyAmount())
                        ->setAmount(1)
                        ->setCurrencyCode('USD')),
            (new LineItem())
                ->setId(0)
                ->setName('product-b')
                ->setLabel('Product B')
                ->setQuantity(2)
                ->setNeedsShipping(true)
                ->setTotalAmount(
                    (new CurrencyAmount())
                        ->setAmount(20)
                        ->setCurrencyCode('USD'))
                ->setTaxAmount(
                    (new CurrencyAmount())
                        ->setAmount(2)
                        ->setCurrencyCode('USD')),
        ];

        // we must use zero as the order ID to prevent WooCommerce from loading
        // that order from the database when we try to convert it into a WC_Order object
        return (new Order)
            ->setId(0)
            ->setNumber('1000001ABC')
            ->setStatus(new ProcessingOrderStatus())
            ->setCreatedAt(new DateTime('now'))
            ->setCustomerIpAddress('192.168.0.1')
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setLineItems($lineItems);
    }

    /**
     * Gets the known placeholders.
     *
     * @return string[]
     */
    public function getPlaceholders() : array
    {
        return [
            'order_number',
            'order_date',
            'order_billing_full_name',
        ];
    }

    /**
     * Gets fake preview data.
     *
     * @return array
     * @throws Exception
     */
    public function getPreviewData() : array
    {
        return $this->getOrderData($this->getPreviewOrder());
    }

    /**
     * Gets a date & time, formatted for display.
     *
     * This is formatted according to WooCommerce's configured date format, localized to the site locale.
     *
     * @param DateTime $dateTime
     * @return string
     * @throws Exception
     */
    protected function getFormattedDateTime(DateTime $dateTime) : string
    {
        return DateTimeRepository::getLocalizedDate(
            DateTimeRepository::getDateFormat(),
            $dateTime->getTimestamp() + $dateTime->getOffset()
        );
    }
}
