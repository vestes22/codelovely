<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\CurrencyAmountAdapter;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\OrderAdapter as CommonOrderAdapter;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\CancelledOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\FailedOrderStatus;
use GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses\RefundedOrderStatus;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order as CoreOrder;

/**
 * Order adapter.
 *
 * Converts between a native core order object and a WooCommerce order object.
 */
class OrderAdapter extends CommonOrderAdapter
{
    /** @var string overrides the common order class with the core order class */
    protected $orderClass = CoreOrder::class;

    /**
     * Converts the order from source WC_Order.
     *
     * @return CoreOrder
     * @throws Exception
     */
    public function convertFromSource() : Order
    {
        /** @var CoreOrder $order */
        $order = parent::convertFromSource();

        if ($emailAddress = $this->source->get_billing_email()) {
            $order->setEmailAddress($emailAddress);
        }

        if ($orderNotes = $this->source->get_customer_note()) {
            $order->setOrderNotes($orderNotes);
        }

        if ('yes' === $this->source->get_meta('_mwc_payments_is_captured')
            || ('poynt' !== $this->source->get_meta('_mwc_transaction_provider_name') && ! empty($this->source->get_date_paid()) && (! empty($this->source->get_transaction_id())))) {
            $order->setCaptured(true);
        } elseif ($this->isOrderReadyForCapture($order)) {
            $order->setReadyForCapture(true);
        }

        if ($remoteId = $this->source->get_meta('_poynt_order_remoteId')) {
            $order->setRemoteId($remoteId);
        }

        if ($createdVia = $this->source->get_created_via()) {
            $order->setSource((string) $createdVia);
        }

        // Order amounts

        $order->setDiscountAmount($this->convertCurrencyAmountFromSource((float) $this->source->get_total_discount()));

        return $order;
    }

    /**
     * Determines whether the order is ready to be captured.
     *
     * TODO: remove status classes from mwc-payments package {@wvega 2021-05-31}.
     *
     * @param Order $order
     * @return bool
     */
    protected function isOrderReadyForCapture(Order $order)
    {
        if (! $this->source->get_meta('_poynt_payment_remoteId')) {
            return false;
        }

        // @TODO: something I don't like about this method: these order status checks imply too much knowledge / dependency on the WC admin. I think the status checks should be done "higher up" near the UI layer, since really these are determining whether to render a WC admin button or not {JS - 2021-10-21}
        // @TODO: something I don't like about this method: 'isOrderReadyForCapture' is assuming an action (capture) rather than returning a state (open authorization). An authorization can be captured or can be voided, so a better method name would probably be something like 'hasOpenAuthorization' or something to that effect, and the calling code can determine what to do with that state {JS - 2021-10-21}
        if ($order->getStatus() instanceof CancelledOrderStatus) {
            return false;
        }

        if ($order->getStatus() instanceof RefundedOrderStatus) {
            return false;
        }

        if ($order->getStatus() instanceof FailedOrderStatus) {
            return false;
        }

        return true;
    }

    /**
     * Converts an order amount from source.
     *
     * @since 3.4.1
     *
     * @param float $amount
     * @return CurrencyAmount
     */
    protected function convertCurrencyAmountFromSource(float $amount) : CurrencyAmount
    {
        return (new CurrencyAmountAdapter($amount, (string) $this->source->get_currency()))->convertFromSource();
    }
}
