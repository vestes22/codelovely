<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\Orders\LineItem;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order as CoreOrder;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CreateOrderRequest;
use GoDaddy\WordPress\MWC\Core\Sync\Jobs\PushSyncJob;

class PushOrdersProducer implements ProducerContract
{
    /** @var string pickup mode */
    const PICKUP_MODE = 'PICKUP';

    /** @var string delivery mode */
    const DELIVERY_MODE = 'DELIVERY';

    /** @var string pickup_instore item */
    const PICKUP_ITEM = 'PICKUP_INSTORE';

    /** @var string ship_to item */
    const SHIP_ITEM = 'SHIP_TO';

    /** @var string Local Delivery shipping method id */
    const LOCAL_DELIVERY_METHOD = 'mwc_local_delivery';

    /** @var array payment methods to exclude transactions */
    const EXCLUDE_TRANSACTIONS_ON = ['poynt', 'godaddy-payments-payinperson', 'cod'];

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
            ->setGroup('mwc_push_poynt_order_objects')
            ->setHandler([$this, 'handlePushOrdersJob'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Handles the job to push orders to the Poynt API.
     *
     * @param int $jobId
     * @param array $orderIds
     * @return void
     * @throws Exception
     */
    public function handlePushOrdersJob(int $jobId, array $orderIds)
    {
        $job = PushSyncJob::get($jobId);
        if (
            ! $job
            || ! WooCommerceRepository::isWooCommerceActive()
            || empty($orderIds)
            || 'order' !== $job->getObjectType()
        ) {
            return;
        }

        try {
            $this->pushOrderToPoynt(ArrayHelper::get($orderIds, 0));
        } catch (Exception $e) {
            $job->setErrors(ArrayHelper::wrap($e->getMessage()));
        }

        $job->update([
            'status' => 'complete',
        ]);
    }

    /**
     * Push order the Poynt API.
     *
     * @param int $orderId
     *
     * @throws Exception
     * @return Response|void
     */
    protected function pushOrderToPoynt(int $orderId)
    {
        if (! ($wcOrder = OrdersRepository::get($orderId))) {
            return;
        }

        $order = (new OrderAdapter($wcOrder))->convertFromSource();

        $response = (new CreateOrderRequest())
            ->body($this->buildCreateOrderBody($order))
            ->send();

        if ($response->isSuccess()) {
            if (! $this->addPoyntOrderId($response, $orderId)) {
                throw new Exception('Could not create order meta for poynt order ID.');
            }
        }

        if ($response->isError() || $response->getStatus() !== 201) {
            $errorMessage = ArrayHelper::get($response->getBody(), 'developerMessage');
            throw new Exception("Could not send the create order ({$response->getStatus()}): {$errorMessage}");
        }

        return $response;
    }

    /**
     * Created request body for Poynt Create Order request.
     *
     * @param CoreOrder $order
     *
     * @throws Exception
     * @return array request body to create an order
     */
    protected function buildCreateOrderBody(CoreOrder $order): array
    {
        $isLocalDelivery = $order->hasShippingMethod(static::LOCAL_DELIVERY_METHOD);

        $body = [
            'items'          => $this->buildOrderBodyLineItems($order->getLineItems(), $isLocalDelivery),
            'orderShipments' => [
                [
                    'deliveryMode' => $isLocalDelivery ? static::DELIVERY_MODE : static::PICKUP_MODE,
                    'status'       => 'NONE',
                    'shipmentType' => 'FULFILLMENT',
                    'address'      => $isLocalDelivery ? $this->buildShippingAddress($order) : null,
                ],
            ],
            'amounts'        => [
                'subTotal'      => $order->getLineAmount() ? $order->getLineAmount()->getAmount() : null,
                'currency'      => $order->getLineAmount() ? $order->getLineAmount()->getCurrencyCode() : null,
                'taxTotal'      => $order->getTaxAmount() ? $order->getTaxAmount()->getAmount() : null,
                'feeTotal'      => $this->getFeeTotalWithShipping($order),
                'discountTotal' => $this->getDiscountTotal($order),
            ],
            'context'        => [
                'source'     => 'WEB',
                'businessId' => Poynt::getBusinessId(),
            ],
            'statuses'       => [
                'status' => 'OPENED',
            ],
            'accepted'       => true,
            'orderNumber'    => $order->getNumber(),
            'notes'          => $this->buildOrderNotes($order),
            'customer'       => [
                'emails'    => [
                    'PERSONAL' => [
                        'emailAddress' => $order->getEmailAddress(),
                    ],
                ],
                'firstName' => $order->getBillingAddress()->getFirstName(),
                'lastName'  => $order->getBillingAddress()->getLastName(),
                'phones'    => [
                    'MOBILE' => [
                        'localPhoneNumber' => $order->getBillingAddress()->getPhone(),
                    ],
                ],
            ],
        ];

        // For GoDaddy Payment orders, the order ID is generated when creating
        // the transaction and passed to the order create request here
        $wcOrder = OrdersRepository::get($order->getId());
        $poyntOrderId = $wcOrder->get_meta('_poynt_order_remoteId');
        if ($poyntOrderId) {
            $body['id'] = $poyntOrderId;
        }

        return $body;
    }

    /**
     * Gets the full amount of all fees in this order including shipping fees.
     *
     * This method's necessary given that Fee Amount doesn't include shipping fees.
     *
     * @param CoreOrder $order
     * @return int|float
     */
    protected function getFeeTotalWithShipping(CoreOrder $order): float
    {
        return
            $this->getFeeItemsTotal($order)
            + ($order->getShippingAmount() ? $order->getShippingAmount()->getAmount() : 0);
    }

    /**
     * Gets the total amount of all the fees items by type in the given order.
     *
     * Returns the sum of positive and negative fee items in this order.
     *
     * @param CoreOrder $order
     * @param bool $hasNegativeFee should return all the fee items with negative values
     * @return float|int
     */
    protected function getFeeItemsTotalByType(CoreOrder $order, $hasNegativeFee = false): float
    {
        $sum = 0;

        foreach (ArrayHelper::wrap($order->getFeeItems()) as $item) {
            $amount = $item->getTotalAmount()->getAmount();

            if ($hasNegativeFee && ($amount < 0)) {
                $sum += abs($amount);
            } elseif (! $hasNegativeFee && ($amount > 0)) {
                $sum += $amount;
            }
        }

        return $sum;
    }

    /**
     * Returns the total amount of negative-value fees items in this order.
     *
     * @param CoreOrder $order
     * @return float|int
     */
    protected function getNegativeFeeItemsTotal(CoreOrder $order): float
    {
        return $this->getFeeItemsTotalByType($order, true);
    }

    /**
     * Returns the total amount of positive-value fees items in this order.
     *
     * @param CoreOrder $order
     * @return float|int
     */
    protected function getFeeItemsTotal(CoreOrder $order): float
    {
        return $this->getFeeItemsTotalByType($order);
    }

    /**
     * Returns the total amount of discounts in this order.
     *
     * @param CoreOrder $order
     * @return float|int
     */
    protected function getDiscountTotal(CoreOrder $order): float
    {
        $discountAmount = $order->getDiscountAmount() ? $order->getDiscountAmount()->getAmount() : 0;

        return -1 * ($this->getNegativeFeeItemsTotal($order) + $discountAmount);
    }

    /**
     * Build Poynt Order line items object.
     *
     * @param LineItem[] $lineItems
     * @param bool $isLocalDelivery
     * @throws Exception
     * @return array
     */
    protected function buildOrderBodyLineItems(array $lineItems, bool $isLocalDelivery): array
    {
        $poyntLineItems = [];

        foreach ($lineItems as $item) {
            $product = $item->getProduct();

            $poyntLineItems[] = [
                'status'                 => 'ORDERED',
                'fulfillmentInstruction' => $isLocalDelivery ? static::SHIP_ITEM : static::PICKUP_ITEM,
                'name'                   => $item->getLabel(),
                'clientNotes'            => $product->is_type('variation') ? $this->buildItemClientNotes($item->getVariationId()) : '',
                'unitOfMeasure'          => 'EACH',
                'sku'                    => $product->get_sku(),
                'unitPrice'              => $item->getSubTotalAmount()->getAmount() / $item->getQuantity(),
                'tax'                    => $item->getSubTotalTaxAmount()->getAmount(),
                'quantity'               => $item->getQuantity(),
            ];
        }

        return $poyntLineItems;
    }

    /**
     * Builds order items client notes for adding attributes/variations.
     *
     * @TODO Add model for more standardized handling of product variations.  We will likely want to be able to fire events off changes to these in the future so extending `Model` gives us an observable entity. {JO: 2021-10-16}
     * @param int $variationId
     * @throws Exception
     * @return string
     */
    protected function buildItemClientNotes(int $variationId): string
    {
        if (! class_exists('\WC_Product_Variation')) {
            throw new Exception('Could not send the new order: WC_Order_Item_Product is missing');
        }
        $productVariation = new \WC_Product_Variation($variationId);

        return $productVariation->get_attribute_summary();
    }

    /**
     * Builds order shipping address for Local Delivery type orders.
     *
     * @param CoreOrder $order
     * @throws Exception
     * @return array|null
     */
    protected function buildShippingAddress(CoreOrder $order)
    {
        return [
            'city'        => $order->getShippingAddress()->getLocality(),
            'countryCode' => $order->getShippingAddress()->getCountryCode(),
            'line1'       => ArrayHelper::get($order->getShippingAddress()->getLines(), 0),
            'line2'       => ArrayHelper::get($order->getShippingAddress()->getLines(), 1),
            'postalCode'  => $order->getShippingAddress()->getPostalCode(),
            'territory'   => ArrayHelper::get($order->getShippingAddress()->getAdministrativeDistricts(), 0),
        ];
    }

    /**
     * Gets the order notes, appending LPP instructions, if applicable.
     *
     * @param CoreOrder $order
     * @return string
     */
    protected function buildOrderNotes(CoreOrder $order) : string
    {
        $notes = $order->getOrderNotes() ?? '';

        if (function_exists('wc_local_pickup_plus') && function_exists('wc_local_pickup_plus_shipping_method')) {
            $pickupData = wc_local_pickup_plus()->get_orders_instance()->get_order_pickup_data($order->getId());

            if (! empty($pickupData)) {
                $template = 'emails/plain/order-pickup-details.php';
                ob_start();
                wc_get_template($template, [
                    'order'           => OrdersRepository::get($order->getId()),
                    'pickup_data'     => $pickupData,
                    'shipping_method' => wc_local_pickup_plus_shipping_method(),
                    'sent_to_admin'   => false,
                ], '', wc_local_pickup_plus()->get_plugin_path().'/templates/');

                $notes .= str_replace('&times;', 'x', ob_get_clean());
            }
        }

        return $notes;
    }

    /**
     * Adds Poynt OrderId to the WC_Order meta.
     *
     * @return bool WC order meta to the remote Poynt Order ID.
     */
    protected function addPoyntOrderId(Response $response, int $orderId) : bool
    {
        return update_post_meta($orderId, '_poynt_order_remoteId', ArrayHelper::get($response->getBody(), 'id'));
    }
}
