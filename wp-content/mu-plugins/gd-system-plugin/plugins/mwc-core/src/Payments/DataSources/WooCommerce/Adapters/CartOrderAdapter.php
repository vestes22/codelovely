<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataSources\WooCommerce\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use WC_Cart;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Cart order adapter.
 *
 * Adapter to convert between a WooCommerce cart and an order object.
 */
class CartOrderAdapter implements DataSourceAdapterContract
{
    /** @var WC_Cart */
    private $source;

    /**
     * Cart order adapter constructor.
     *
     * @param WC_Cart $cart
     */
    public function __construct(WC_Cart $cart)
    {
        $this->source = $cart;
    }

    /**
     * Adapts a WooCommerce cart into an order object.
     *
     * @param array|null $orderData optional data to override any default cart data
     * @param array|null $metaData optional additional meta data to store on the order
     * @return Order
     * @throws Exception
     */
    public function convertFromSource(array $orderData = null, array $metaData = null) : Order
    {
        $this->source->calculate_totals();

        $order = $this->getWooCommerceOrderFromCart(is_array($orderData) ? $orderData : []);
        $order = $this->setWooCommerceOrderMetaData($order, is_array($metaData) ? $metaData : []);

        $wc = WooCommerceRepository::getInstance();
        $checkout = $wc->checkout();

        $checkout->create_order_line_items($order, $this->source);
        $checkout->create_order_coupon_lines($order, $this->source);
        $checkout->create_order_shipping_lines($order, $wc->session->get('chosen_shipping_methods', []), $wc->shipping()->get_packages());
        $checkout->create_order_fee_lines($order, $this->source);
        $checkout->create_order_tax_lines($order, $this->source);

        do_action('woocommerce_checkout_create_order', $order, []);

        $order->save();
        $order->update_taxes();
        $order->calculate_totals(false); // false to skip recalculating taxes

        do_action('woocommerce_checkout_update_order_meta', $order->get_id(), []);

        try {
            return $this->getAdaptedOrder($order);
        } catch (Exception $exception) {
            throw new Exception($this->getCreateOrderFromCartErrorMessage());
        }
    }

    /**
     * Gets an adapted order from a WooCommerce order.
     *
     * @param WC_Order $order
     * @return Order
     * @throws Exception
     */
    protected function getAdaptedOrder(WC_Order $order) : Order
    {
        return (new OrderAdapter($order))->convertFromSource();
    }

    /**
     * Sets metadata on a WooCommerce order.
     *
     * @param WC_Order $order
     * @param array $metaData
     * @return WC_Order
     */
    protected function setWooCommerceOrderMetaData(WC_Order $order, array $metaData) : WC_Order
    {
        if ($wooCustomer = $this->source->get_customer()) {
            $order->add_meta_data('is_vat_exempt', $wooCustomer->get_is_vat_exempt() ? 'yes' : 'no');
        }

        foreach ($metaData as $key => $value) {
            $order->add_meta_data($key, $value);
        }

        return $order;
    }

    /**
     * Gets a WooCommerce order object from the cart.
     *
     * @param array $orderData
     * @return WC_Order
     * @throws Exception
     */
    protected function getWooCommerceOrderFromCart(array $orderData) : WC_Order
    {
        $wc = WooCommerceRepository::getInstance();
        $orderId = (int) $wc->session->get('order_awaiting_payment', 0);
        $order = OrdersRepository::get($orderId);
        $orderData = ArrayHelper::combine([
            'status'      => $this->getDefaultWooCommerceOrderStatus(),
            'customer_id' => $this->getCheckoutCustomerId(),
            'cart_hash'   => $this->getCartHash(),
        ], $orderData);

        if ($order && $order->has_cart_hash($orderData['cart_hash']) && $order->has_status(['pending', 'failed'])) {
            $orderData['order_id'] = $orderId;

            $order = wc_update_order($orderData);

            if (is_wp_error($order)) {
                throw new Exception($this->getCreateOrderFromCartErrorMessage(522));
            }

            do_action('woocommerce_resume_order', $orderId);

            $order->remove_order_items();
        } else {
            $order = wc_create_order($orderData);

            if (is_wp_error($order)) {
                throw new Exception($this->getCreateOrderFromCartErrorMessage(520));
            }

            if (! $order instanceof WC_Order) {
                throw new Exception($this->getCreateOrderFromCartErrorMessage(521));
            }

            // set the new order ID, so it can be resumed in case of failure
            $wc->session->set('order_awaiting_payment', $order->get_id());
        }

        return $order;
    }

    /**
     * Gets the cart hash.
     *
     * @return string
     */
    protected function getCartHash() : string
    {
        $contentFromSession = $this->source->get_cart_for_session() ?? '';

        return md5(json_encode(wc_clean($contentFromSession)).(string) $this->source->get_total('edit'));
    }

    /**
     * Gets the customer ID for checkout.
     *
     * @return int
     */
    protected function getCheckoutCustomerId() : int
    {
        $id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());

        return is_numeric($id) ? (int) $id : 0;
    }

    /**
     * Gets the default WooCommerce order status to use.
     *
     * @return string
     */
    protected function getDefaultWooCommerceOrderStatus() : string
    {
        $default = 'pending';
        $status = apply_filters('woocommerce_default_order_status', $default);

        return is_string($status) ? $status : $default;
    }

    /**
     * Gets an error message when unable to create or update an order from cart.
     *
     * @param int $errorCode
     * @return string
     */
    private function getCreateOrderFromCartErrorMessage(int $errorCode = 500) : string
    {
        /* translators: Placeholder: %d - error code */
        return sprintf(__('Error %d: Unable to create order. Please try again.', 'mwc-core'), $errorCode);
    }

    /**
     * Adapts an order object to a WooCommerce cart.
     *
     * @param Order $order
     * @return WC_Cart
     * @throws Exception
     */
    public function convertToSource(Order $order = null) : WC_Cart
    {
        if (null === $order) {
            return $this->source;
        }

        $order = $this->getWooCommerceAdaptedOrder($order);

        foreach ($order->get_items() as $lineItem) {
            /* @var WC_Order_Item_Product $lineItem */
            $product = $lineItem->get_product();

            if (! $product) {
                continue;
            }

            $productId = $product->get_id();
            $parentId = $product->get_parent_id();

            $this->source->add_to_cart($parentId ?: $productId, $lineItem->get_quantity(), $productId, $lineItem->get_data());
        }

        $this->source->calculate_totals();

        return $this->source;
    }

    /**
     * Gets an adapted WooCommerce order.
     *
     * @param Order $order
     * @return WC_Order
     * @throws Exception
     */
    protected function getWooCommerceAdaptedOrder(Order $order) : WC_Order
    {
        return (new OrderAdapter(new WC_Order()))->convertToSource($order);
    }
}
