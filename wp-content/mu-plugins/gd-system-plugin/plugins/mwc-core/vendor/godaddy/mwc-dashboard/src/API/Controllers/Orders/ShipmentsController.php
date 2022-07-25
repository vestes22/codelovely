<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Orders;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\AdaptsShipmentDataTrait;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\OrderNotFoundException;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\ShipmentNotFoundException;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\ShipmentValidationFailedException;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\DataStores\ShipmentTracking\OrderFulfillmentDataStore;
use GoDaddy\WordPress\MWC\Dashboard\Shipping\Fulfillment;
use GoDaddy\WordPress\MWC\Shipping\Contracts\ShipmentContract;
use GoDaddy\WordPress\MWC\Shipping\Models\Orders\OrderFulfillment;
use WP_REST_Request;

class ShipmentsController extends AbstractController
{
    use AdaptsShipmentDataTrait;
    use RequiresWooCommercePermissionsTrait;

    /**
     * Route.
     *
     * @var string
     */
    protected $route = 'orders/(?P<orderId>[0-9]+)/shipments';

    /**
     * Registers the API routes for the endpoints provided by the controller.
     *
     * @since x.y.z
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace,
            "/{$this->route}",
            [
                [
                    'methods'             => 'POST',
                    'callback'            => [$this, 'createItem'],
                    'permission_callback' => [$this, 'createItemPermissionsCheck'],
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
            ]
        );

        register_rest_route(
            $this->namespace,
            "/{$this->route}",
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'getItems'],
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
            ]
        );

        register_rest_route(
            $this->namespace,
            "/{$this->route}/(?P<shipmentId>[a-zA-Z0-9_-]+)",
            [
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
                    'shipmentId' => [
                        'required'          => true,
                        'type'              => 'string',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            "/{$this->route}/(?P<shipmentId>[a-zA-Z0-9_-]+)",
            [
                [
                    'methods'             => 'PUT',
                    'callback'            => [$this, 'updateItem'],
                    'permission_callback' => [$this, 'updateItemPermissionsCheck'],
                ],
                'args' => [
                    'orderId' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                    'shipmentId' => [
                        'required'          => true,
                        'type'              => 'string',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            "/{$this->route}/(?P<shipmentId>[a-zA-Z0-9_-]+)",
            [
                [
                    'methods'             => 'DELETE',
                    'callback'            => [$this, 'deleteItem'],
                    'permission_callback' => [$this, 'deleteItemPermissionsCheck'],
                ],
                'args' => [
                    'orderId' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                    'shipmentId' => [
                        'required'          => true,
                        'type'              => 'string',
                        'validate_callback' => 'rest_validate_request_arg',
                        'sanitize_callback' => 'rest_sanitize_request_arg',
                    ],
                ],
            ]
        );
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @since x.y.z
     *
     * @return array
     */
    public function getItemSchema(): array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'shipment',
            'type'       => 'object',
            'properties' => [
                'id'             => [
                    'description' => __('The shipment ID.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'orderId'             => [
                    'description' => __('The order ID.', 'mwc-dashboard'),
                    'type'        => 'integer',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'createdAt' => [
                    'description' => __("The shipment's creation date.", 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'updatedAt' => [
                    'description' => __("The shipment's last updated date.", 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'shippingProvider' => [
                    'description' => __('The shipping provider for the shipment.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                ],
                'trackingNumber' => [
                    'description' => __("The shipment's tracking number.", 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                ],
                'trackingUrl' => [
                    'description' => __("The shipment's tracking URL.", 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                ],
                'items' => [
                    'description' => __('The items included in the shipment.', 'mwc-dashboard'),
                    'type'        => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'description' => __("The item's ID.", 'mwc-dashboard'),
                                'type'        => 'integer',
                                'context'     => ['view', 'edit'],
                            ],
                            'productId' => [
                                'description' => __("The product's ID.", 'mwc-dashboard'),
                                'type'        => 'integer',
                                'context'     => ['view', 'edit'],
                            ],
                            'variationId' => [
                                'description' => __("The product's variation ID.", 'mwc-dashboard'),
                                'type'        => 'integer',
                                'context'     => ['view', 'edit'],
                            ],
                            'quantity' => [
                                'description' => __("The item's quantity.", 'mwc-dashboard'),
                                'type'        => 'number',
                                'context'     => ['view', 'edit'],
                            ],
                        ],
                    ],
                    'context'     => ['view', 'edit'],
                ],
            ],
        ];
    }

    /**
     * Gets a REST response with all of the Shipments.
     *
     * @since x.y.z
     */
    public function getItems(WP_REST_Request $request)
    {
        try {
            // there is no need to check if the orderId is an integer,
            // the pattern on the register_rest_route() call already guarantees that
            $orderId = (int) $request->get_param('orderId');
            $fulfillment = $this->getOrderFulfillment($orderId);

            $responseData = ['shipments' => []];

            foreach ($fulfillment->getShipments() as $shipment) {
                $responseData['shipments'][] = $this->prepareItem($fulfillment, $shipment);
            }

            (new Response)
                ->body($responseData)
                ->success(200)
                ->send();
        } catch (BaseException $exception) {
            (new Response)
                ->error([$exception->getMessage()], $exception->getCode())
                ->send();
        }
    }

    /**
     * Gets an OrderFulfillment object with the order ID included in the request.
     *
     * @since x.y.z
     *
     * @return OrderFulfillment
     *
     * @throws \GoDaddy\WordPress\MWC\Dashboard\Exceptions\OrderNotFoundException
     */
    protected function getOrderFulfillmentFromRequest(WP_REST_Request $request) : OrderFulfillment
    {
        // there is no need to check if the orderId is an integer,
        // the pattern on the register_rest_route() call already guarantees that
        $orderId = (int) $request->get_param('orderId');

        return $this->getOrderFulfillment($orderId);
    }

    /**
     * Gets an OrderFulfillment object with the given order id.
     *
     * @since x.y.z
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
     * @since x.y.z
     *
     * @return OrderFulfillmentDataStore
     */
    protected function getOrderFulfillmentDataStore() : OrderFulfillmentDataStore
    {
        return new OrderFulfillmentDataStore();
    }

    /**
     * Prepares the Shipment data.
     *
     * @since x.y.z
     *
     * @param OrderFulfillment $fulfillment
     * @param ShipmentContract $shipment
     * @return array
     */
    protected function prepareItem(OrderFulfillment $fulfillment, ShipmentContract $shipment) : array
    {
        return $this->getShipmentData($shipment) + ['orderId' => $fulfillment->getOrder()->getId()];
    }

    /**
     * Creates an item.
     *
     * @param WP_REST_Request $request
     * @throws Exception
     */
    public function createItem(WP_REST_Request $request)
    {
        try {
            $fulfillment = $this->getOrderFulfillmentFromRequest($request);

            $payload = $request->get_json_params();
            $shipment = $this->addShipment($fulfillment, $payload);

            if (ArrayHelper::get($payload, 'shouldUpdateOrderStatus', false)) {
                $this->setOrderStatusToComplete($fulfillment);
            }

            $responseData = [
                'shipment' => $this->prepareItem($fulfillment, $shipment),
            ];

            (new Response)
                ->body($responseData)
                ->success(200)
                ->send();
        } catch (Exception $exception) {
            (new Response)
                ->error([$exception->getMessage()], 400)
                ->send();
        }
    }

    /**
     * Converts the shipment data into an instance of ShipmentContract and adds it to the order's fulfillment data.
     *
     * @param OrderFulfillment $fulfillment
     * @param array $data
     * @return ShipmentContract
     * @throws ShipmentValidationFailedException
     */
    protected function addShipment(OrderFulfillment $fulfillment, array $data) : ShipmentContract
    {
        $shipment = $this->getShipmentFromRequestData($data);

        if (! $this->validatePackageQuantity($shipment)) {
            throw new ShipmentValidationFailedException('Shipment provided without any items.');
        }

        Fulfillment::getInstance()->addShipment($fulfillment, $shipment);

        return $shipment;
    }

    /**
     * Sets the order's status to complete.
     *
     * @param OrderFulfillment $fulfillment
     * @throws Exception
     */
    protected function setOrderStatusToComplete(OrderFulfillment $fulfillment)
    {
        $order = OrdersRepository::get((int) $fulfillment->getOrder()->getId());

        if (! $order) {
            return;
        }

        $order->set_status('wc-completed');
        $order->save();
    }

    /**
     * Updates an item.
     *
     * @param WP_REST_Request $request
     * @throws Exception
     */
    public function updateItem(WP_REST_Request $request)
    {
        try {
            $orderFulfillment = $this->getOrderFulfillmentFromRequest($request);

            $shipmentId = StringHelper::sanitize($request->get_param('shipmentId'));
            $payload = $request->get_json_params();

            $shipment = $this->updateShipment($orderFulfillment, $shipmentId, $payload);

            if (ArrayHelper::get($payload, 'shouldUpdateOrderStatus', false)) {
                $this->setOrderStatusToComplete($orderFulfillment);
            }

            (new Response)
                ->body([
                    'shipment' => $this->prepareItem($orderFulfillment, $shipment),
                ])
                ->success(200)
                ->send();
        } catch (BaseException $exception) {
            (new Response())
                ->error([$exception->getMessage()], $exception->getCode())
                ->send();
        } catch (Exception $exception) {
            (new Response())
                ->error([$exception->getMessage()], 400)
                ->send();
        }
    }

    /**
     * Converts the shipment data into an instance of ShipmentContract and updates the order's fulfillment data.
     *
     * @since x.y.z
     *
     * @param OrderFulfillment $fulfillment
     * @param string $shipmentId
     * @param array $data
     *
     * @return ShipmentContract
     * @throws BaseException
     */
    protected function updateShipment(OrderFulfillment $fulfillment, string $shipmentId, array $data): ShipmentContract
    {
        $shipment = $this->getShipmentFromRequestData($data);

        if (! $this->validatePackageQuantity($shipment)) {
            throw new ShipmentValidationFailedException('Shipment provided without any items.');
        }

        Fulfillment::getInstance()->updateShipment($fulfillment, $shipmentId, $shipment);

        return $shipment;
    }

    /**
     * Gets an item.
     *
     * @since x.y.z
     * @internal
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function getItem(WP_REST_Request $request)
    {
        try {
            $fulfillment = $this->getOrderFulfillmentFromRequest($request);
            $shipment = $this->getShipment($fulfillment, StringHelper::sanitize($request->get_param('shipmentId')));

            $responseData = [
                'shipment' => $this->prepareItem($fulfillment, $shipment),
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
     * Gets the Shipment object from the OrderFulfillment object using the given shipmentId.
     *
     * @since x.y.z
     *
     * @param OrderFulfillment $fulfillment
     * @param string $shipmentId
     * @return ShipmentContract
     *
     * @throws ShipmentNotFoundException
     */
    protected function getShipment(OrderFulfillment $fulfillment, string $shipmentId) : ShipmentContract
    {
        $shipment = $fulfillment->getShipment($shipmentId);

        if (empty($shipment)) {
            throw new ShipmentNotFoundException("Shipment not found with ID {$shipmentId}");
        }

        return $shipment;
    }

    /**
     * Deletes an item.
     *
     * @since x.y.z
     *
     * @param WP_REST_Request $request
     */
    public function deleteItem(WP_REST_Request $request)
    {
        try {
            // there is no need to check if the orderId is an integer,
            // the pattern on the register_rest_route() call already guarantees that
            $orderId = (int) StringHelper::sanitize($request->get_param('orderId'));
            $fulfillment = $this->getOrderFulfillment($orderId);

            $shipmentId = StringHelper::sanitize($request->get_param('shipmentId'));
            $this->deleteShipment($fulfillment, $shipmentId);

            (new Response)
                ->success(204)
                ->send();
        } catch (Exception $exception) {
            (new Response)
                ->error([$exception->getMessage()], 400)
                ->send();
        }
    }

    /**
     * Removes the specified shipment from the order's fulfillment data.
     *
     * @param OrderFulfillment $fulfillment
     * @param string $shipmentId
     *
     * @throws BaseException
     */
    protected function deleteShipment(OrderFulfillment $fulfillment, string $shipmentId)
    {
        /** @var Fulfillment $fulfillmentHandler */
        $fulfillmentHandler = Fulfillment::getInstance();

        $fulfillmentHandler->deleteShipment($fulfillment, $shipmentId);
    }

    /**
     * Validates that a Shipment is provided with items.
     *
     * @since x.y.z
     *
     * @param ShipmentContract $shipment
     * @return bool
     */
    protected function validatePackageQuantity(ShipmentContract $shipment) : bool
    {
        $packages = $shipment->getPackages();

        if (empty($packages)) {
            return false;
        }

        foreach ($packages as $package) {
            if (empty($package->getItems())) {
                return false;
            }
        }

        return true;
    }
}
