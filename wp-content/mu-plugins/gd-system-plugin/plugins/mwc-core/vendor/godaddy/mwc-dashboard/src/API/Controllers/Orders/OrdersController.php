<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Orders;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\AdaptsShipmentDataTrait;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\OrderNotFoundException;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\ShipmentTracking\OrderFulfillmentDataStore;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses\FulfilledFulfillmentStatus;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;
use WC_Order;
use WP_REST_Request;

/**
 * Orders controller.
 */
class OrdersController extends AbstractController
{
    use AdaptsShipmentDataTrait;
    use RequiresWooCommercePermissionsTrait;

    /**
     * Route.
     *
     * @var string
     */
    protected $route = 'orders';

    /**
     * Registers the API routes for the orders endpoint.
     *
     * @internal
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            'args' => [
                'include' => [
                    'required'          => false,
                    'type'              => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
                'query' => [
                    'required'          => false,
                    'type'              => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/(?P<orderId>[0-9]+)", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            'args' => [
                'orderId' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);
    }

    /**
     * Gets the schema for REST items provided by the controller.
     *
     * @internal
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'orders',
            'type'    => 'array',
            'items'   => [
                'type'       => 'object',
                'properties' => [
                    'id'        => [
                        'description' => __('The order ID.', 'mwc-dashboard'),
                        'type'        => 'integer',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'fulfilled' => [
                        'description' => __('Whether or not the order has been fulfilled.', 'mwc-dashboard'),
                        'type'        => 'bool',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'shipments' => [
                        'description' => __('The shipments for the order.', 'mwc-dashboard'),
                        'type'        => 'array',
                        'items'       => [
                            'type'       => 'object',
                            'properties' => [
                                'id'               => [
                                    'description' => __('The shipment ID.', 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'orderId'          => [
                                    'description' => __('The order ID.', 'mwc-dashboard'),
                                    'type'        => 'integer',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'createdAt'        => [
                                    'description' => __("The shipment's creation date.", 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'updatedAt'        => [
                                    'description' => __("The shipment's last updated date.", 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'shippingProvider' => [
                                    'description' => __('The shipping provider for the shipment.', 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'trackingNumber'   => [
                                    'description' => __("The shipment's tracking number.", 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'trackingUrl'      => [
                                    'description' => __("The shipment's tracking URL.", 'mwc-dashboard'),
                                    'type'        => 'string',
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                                'items'            => [
                                    'description' => __('The items included in the shipment.', 'mwc-dashboard'),
                                    'type'        => 'array',
                                    'items'       => [
                                        'type'       => 'object',
                                        'properties' => [
                                            'id'          => [
                                                'description' => __("The item's ID.", 'mwc-dashboard'),
                                                'type'        => 'integer',
                                                'context'     => ['view', 'edit'],
                                                'readonly'    => true,
                                            ],
                                            'productId'   => [
                                                'description' => __("The product's ID.", 'mwc-dashboard'),
                                                'type'        => 'integer',
                                                'context'     => ['view', 'edit'],
                                                'readonly'    => true,
                                            ],
                                            'variationId' => [
                                                'description' => __("The product's variation ID.", 'mwc-dashboard'),
                                                'type'        => 'integer',
                                                'context'     => ['view', 'edit'],
                                                'readonly'    => true,
                                            ],
                                            'quantity'    => [
                                                'description' => __("The item's quantity.", 'mwc-dashboard'),
                                                'type'        => 'number',
                                                'context'     => ['view', 'edit'],
                                                'readonly'    => true,
                                            ],
                                        ],
                                    ],
                                    'context'     => ['view', 'edit'],
                                    'readonly'    => true,
                                ],
                            ],
                        ],
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'emailAddress' => [
                        'description' => __('The order email address.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'number' => [
                        'description' => __('The order number, distinct from the order ID.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'paid' => [
                        'description' => __('Whether the order is considered "paid."', 'mwc-dashboard'),
                        'type'        => 'bool',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'paymentProviderName' => [
                        'description' => __('The payment provider name (in Woo terms, gateway ID).', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'status' => [
                        'description' => __('The overall order status.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'totalAmount' => [
                        'description' => __('The order total amount', 'mwc-dashboard'),
                        'type'        => 'object',
                        'properties'       => [
                            'amount'               => [
                                'description' => __('The full order amount, in the smallest unit of the given currencyCode.', 'mwc-dashboard'),
                                'type'        => 'integer',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'currencyCode'          => [
                                'description' => __('The currency code.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                    'billingAddress' => [
                        'description' => __('The order billing address.', 'mwc-dashboard'),
                        'type'        => 'object',
                        'properties'       => [
                            'administrativeDistricts'               => [
                                'description' => __('An array of administrative districts.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'businessName'               => [
                                'description' => __('The billing address business name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'countryCode'               => [
                                'description' => __('The billing address country code.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'firstName'               => [
                                'description' => __('The billing address customers first name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'lastName'               => [
                                'description' => __('The billing address customers last name.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'lines'               => [
                                'description' => __('The billing address lines.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'locality'               => [
                                'description' => __('The billing address locality.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'phoneNumber' => [
                                'description' => __('The billing address phone number.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'postalCode'               => [
                                'description' => __('The billing address postal code.', 'mwc-dashboard'),
                                'type'        => 'string',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                            'subLocalities'               => [
                                'description' => __('The billing address sub localities.', 'mwc-dashboard'),
                                'type'        => 'array',
                                'context'     => ['view', 'edit'],
                                'readonly'    => true,
                            ],
                        ],
                        'context'     => ['view', 'edit'],
                        'readonly'    => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Sends a REST response with orders.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @throws Exception
     */
    public function getItems(WP_REST_Request $request)
    {
        $orderIds = [];
        $resources = [];

        if (! empty($queryParam = $request->get_param('query'))) {
            $query = json_decode($queryParam, true);
            $orderIds = ArrayHelper::wrap(ArrayHelper::get($query, 'ids'));
            $resources = ArrayHelper::wrap(ArrayHelper::get($query, 'includes'));
        }

        if (empty($orderIds)) {
            $orderIds = wc_get_orders([
                'limit' => 20,
                'orderby' => 'date',
                'order' => 'DESC',
                'return' => 'ids',
            ]);
        }

        $responseData = [];
        $dataStore = new OrderFulfillmentDataStore();

        foreach ($orderIds as $orderId) {
            $fulfillment = $dataStore->read($orderId);

            if ($fulfillment) {
                $responseData[] = $this->prepareItem($fulfillment, $resources);
            }
        }

        (new Response)
            ->body(['orders' => $responseData])
            ->success(200)
            ->send();
    }

    /**
     * Sends a REST response with order based on id.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @throws Exception
     */
    public function getItem(WP_REST_Request $request)
    {
        try {
            $order = $this->getOrderFulfillmentFromRequest($request);

            $responseData = [
                'order' => $this->prepareItem($order),
            ];

            (new Response)
                ->body($responseData)
                ->success(200)
                ->send();
        } catch (BaseException $exception) {
            (new Response)
                ->error([$exception->getMessage()], $exception->getCode())
                ->send();
        } catch (Exception $exception) {
            (new Response)
                ->error([$exception->getMessage()], 400)
                ->send();
        }
    }

    /**
     * Prepares the given order object for API response.
     *
     * @param OrderFulfillment $fulfillment
     * @param array $resources
     * @return array
     * @throws Exception
     */
    protected function prepareItem(OrderFulfillment $fulfillment, array $resources = []) : array
    {
        $order = $fulfillment->getOrder();
        $orderId = $order->getId();

        $status = $order->getStatus();
        $totalAmount = $order->getTotalAmount();
        $billingAddress = $order->getBillingAddress();

        $itemData = [
            'id' => $orderId,
            'fulfilled' => $order->getFulfillmentStatus() instanceof FulfilledFulfillmentStatus,
            'number' => $order->getNumber(),
            'status' => $status ? strtoupper($status->getName()) : '',
            'totalAmount' => $totalAmount ? [
                'amount' => $totalAmount->getAmount(),
                'currencyCode' => $totalAmount->getCurrencyCode(),
            ] : '',
            'billingAddress' => $billingAddress ? [
                'administrativeDistricts' => $billingAddress->getAdministrativeDistricts(),
                'businessName' => $billingAddress->getBusinessName(),
                'countryCode' => $billingAddress->getCountryCode(),
                'firstName' => $billingAddress->getFirstName(),
                'lastName' => $billingAddress->getLastName(),
                'lines' => $billingAddress->getLines(),
                'locality' => $billingAddress->getLocality(),
                'phoneNumber' => preg_replace('/[^+0-9]/', '', $billingAddress->getPhone()),
                'postalCode' => $billingAddress->getPostalCode(),
                'subLocalities' => $billingAddress->getSubLocalities(),
            ] : '',
        ];

        if ($wooOrder = OrdersRepository::get($orderId)) {
            $itemData = ArrayHelper::combine($itemData, $this->getWooOrderData($wooOrder));
        }

        if (ArrayHelper::contains($resources, 'shipments')) {
            $itemData['shipments'] = $this->prepareShipmentItems($fulfillment);
        }

        return $itemData;
    }

    /**
     * Returns data associated with WooCommerce order objects.
     *
     * @param WC_Order $order
     * @return array
     */
    protected function getWooOrderData(WC_Order $order) : array
    {
        return [
            'emailAddress' => $this->getEmail($order),
            'paid' => $this->isPaid($order),
            'paymentProviderName' => $this->getProviderName($order),
        ];
    }

    /**
     * Returns the email address associated with the order.
     *
     * @param WC_Order $order
     * @return string|null
     */
    protected function getEmail(WC_Order $order)
    {
        return $order->get_billing_email();
    }

    /**
     * Returns whether the order is paid for based on the order status.
     *
     * @param WC_Order $order
     * @return bool
     */
    protected function isPaid(WC_Order $order) : bool
    {
        return $order->is_paid();
    }

    /**
     * Returns whether the order is paid for based on the order status.
     *
     * @param WC_Order $order
     * @return string
     */
    protected function getProviderName(WC_Order $order) : string
    {
        return $order->get_payment_method();
    }

    /**
     * Prepares the shipment items in the given fulfillment object for API response.
     *
     * @param OrderFulfillment $fulfillment
     * @return array
     *
     * @throws Exception
     */
    protected function prepareShipmentItems(OrderFulfillment $fulfillment) : array
    {
        $shipmentData = [];

        foreach ($fulfillment->getShipments() as $shipment) {
            $shipmentData[] = $this->getShipmentData($shipment);
        }

        return $shipmentData;
    }

    /**
     * Gets an OrderFulfillment object with the order ID included in the request.
     *
     * @param WP_REST_Request $request
     * @return OrderFulfillment
     *
     * @throws OrderNotFoundException
     */
    protected function getOrderFulfillmentFromRequest(WP_REST_Request $request) : OrderFulfillment
    {
        $orderId = (int) $request->get_param('orderId');

        return $this->getOrderFulfillment($orderId);
    }

    /**
     * Gets an OrderFulfillment object with the given order id.
     *
     * @param int $orderId
     * @return OrderFulfillment
     *
     * @throws OrderNotFoundException
     */
    protected function getOrderFulfillment(int $orderId) : OrderFulfillment
    {
        $fulfillment = ($this->getOrderFulfillmentDataStore())->read($orderId);

        if (empty($fulfillment)) {
            throw new OrderNotFoundException("Order not found with ID {$orderId}");
        }

        return $fulfillment;
    }

    /**
     * Returns an instance of OrderFulfillmentDataStore.
     *
     * @return OrderFulfillmentDataStore
     */
    protected function getOrderFulfillmentDataStore() : OrderFulfillmentDataStore
    {
        return new OrderFulfillmentDataStore();
    }
}
