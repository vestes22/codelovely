<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\AbstractPaymentGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * ProviderProcessingController extends AbstractProviderController for handling endpoints.
 */
class ProviderProcessingController extends AbstractProviderController
{
    /**
     * Initializes the controller.
     *
     * @since x.y.z
     */
    public function load()
    {
        $this->registerRoutes();
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     *
     * @since x.y.z
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, '/'.$this->route.'/processing/pay', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'payItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
                'args' => $this->getPayItemArgs(),
                'schema' => [$this, 'getItemSchema'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->route.'/processing/void', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'voidItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
                'args' => $this->getVoidItemArgs(),
                'schema' => [$this, 'getItemSchema'],
            ],
        ]);
    }

    /**
     * Handles the pay transaction endpoint response.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function payItem(WP_REST_Request $request)
    {
        try {
            $gateway = $this->getRequestManagedPaymentGateway($request);
            $order = $this->getRequestWooCommerceOrder($request);
            $transaction = $this->updateTransaction($gateway->getTransactionForPayment($order), $request->get_json_params());
            $transaction = $gateway->processPayment($transaction);

            $this->updateWooCommerceOrder($transaction, $order);

            $response = $this->prepareTransaction($transaction);
        } catch (Exception $exception) {
            $response = $this->prepareError($exception);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets the arguments' schema for the /pay endpoint.
     *
     * @return array
     */
    protected function getPayItemArgs() : array
    {
        return [
            'authOnly' => [
                'type'     => 'boolean',
                'required' => false,
            ],
            'billingAddress' => [
                'required' => false,
                'type' => 'object',
                'properties' => [
                    'lines' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    'postalCode' => [
                        'type' => 'string',
                    ],
                ],
            ],
            'emailAddress' => [
                'type' => 'string',
            ],
            'orderId' => [
                'required' => true,
                'type'     => 'integer',
            ],
            'paymentMethod' => [
                'required' => true,
                'type'     => 'object',
                'properties' => [
                    'nonce' => [
                        'type'     => 'string',
                        'required' => true,
                    ],
                ],
            ],
            'phoneNumber' => [
                'type' => 'string',
                'required' => false,
            ],
            'source' => [
                'type' => 'string',
            ],
        ];
    }

    /**
     * Updates the given transaction with request data.
     *
     * @param PaymentTransaction $transaction
     * @param array $requestData
     *
     * @return PaymentTransaction
     * @throws Exception
     */
    protected function updateTransaction(PaymentTransaction $transaction, array $requestData) : PaymentTransaction
    {
        $transaction->setAuthOnly((bool) ArrayHelper::get($requestData, 'authOnly'));

        if ($source = StringHelper::sanitize((string) ArrayHelper::get($requestData, 'source'))) {
            $transaction->setSource($source);
        }

        $transaction->getPaymentMethod()->setRemoteId(StringHelper::sanitize((string) ArrayHelper::get($requestData, 'paymentMethod.nonce')));

        if ($order = $transaction->getOrder()) {
            $this->updateOrder($order, $requestData);
        }

        return $transaction;
    }

    /**
     * Updates the given order with request data.
     *
     * @param Order $order
     * @param array $requestData
     *
     * @return Order
     * @throws Exception
     */
    protected function updateOrder(Order $order, array $requestData) : Order
    {
        $billingAddress = $order->getBillingAddress();

        if ($lines = array_filter(array_map(StringHelper::class.'::sanitize', (array) ArrayHelper::get($requestData, 'billingAddress.lines')))) {
            $billingAddress->setLines($lines);
        }

        if ($postalCode = StringHelper::sanitize((string) ArrayHelper::get($requestData, 'billingAddress.postalCode'))) {
            $billingAddress->setPostalCode($postalCode);
        }

        if ($phoneNumber = StringHelper::sanitize((string) ArrayHelper::get($requestData, 'phoneNumber'))) {
            $billingAddress->setPhone($this->validatePhoneNumber($phoneNumber));
        }

        $emailAddress = StringHelper::sanitize((string) ArrayHelper::get($requestData, 'emailAddress'));

        if (ValidationHelper::isEmail($emailAddress)) {
            $order->setEmailAddress($emailAddress);
        }

        return $order;
    }

    /**
     * Validates a phone number string.
     *
     * This only allows a leading + character and any number of integers.
     *
     * @param string $value
     *
     * @return string
     * @throws Exception
     */
    protected function validatePhoneNumber(string $value)
    {
        $value = preg_replace('/[^+0-9]/', '', trim($value));

        // bail if the value contains no digits
        if (! $value || '+' === $value) {
            throw new Exception(__('Invalid phone number.', 'mwc-core'));
        }

        return $value;
    }

    /**
     * Updates the WooCommerce order given payment transaction results.
     *
     * @param PaymentTransaction $transaction
     * @param WC_Order $wcOrder
     * @throws Exception
     */
    protected function updateWooCommerceOrder(PaymentTransaction $transaction, WC_Order $wcOrder)
    {
        $wcOrder->set_payment_method($transaction->getProviderName());

        $status = $transaction->getStatus();

        if ($status instanceof DeclinedTransactionStatus) {
            $wcOrder->update_status('failed');
        } elseif ($status instanceof ApprovedTransactionStatus && ! $transaction->isAuthOnly()) {
            // also marks order as processing and decreases stock levels
            $wcOrder->payment_complete($transaction->getRemoteId());
        } else {
            $wcOrder->update_status('on-hold');
            // must decrease stock levels manually
            wc_reduce_stock_levels($wcOrder->get_id());
        }
    }

    /**
     * Handles the void transaction endpoint response.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function voidItem(WP_REST_Request $request)
    {
        try {
            $gateway = $this->getRequestManagedPaymentGateway($request);
            $order = $this->getRequestWooCommerceOrder($request);
            $transaction = $gateway->getTransactionForVoid($order);

            $parentId = $request->get_param('remoteParentId');

            if ($parentId && $transaction->getRemoteParentId() !== $parentId) {
                throw new Exception(__('The remote parent ID in the request does not match the remote parent ID of the order.', 'mwc-core'), 400);
            }

            $response = $this->prepareTransaction($gateway->processVoid($transaction));
        } catch (Exception $exception) {
            $response = $this->prepareError($exception);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets the arguments' schema for the /void endpoint.
     *
     * @return array[]
     */
    protected function getVoidItemArgs() : array
    {
        return [
            'orderId' => [
                'type' => 'integer',
                'required' => true,
            ],
            'remoteParentId' => [
                'type' => 'string',
                'required' => false,
            ],
        ];
    }

    /**
     * Prepares the data for a transaction.
     *
     * @param AbstractTransaction $transaction
     *
     * @return array
     */
    protected function prepareTransaction(AbstractTransaction $transaction) : array
    {
        $data = [
            'avsResult'      => $transaction instanceof PaymentTransaction ? $transaction->getAvsResult() : null,
            'cvvResult'      => $transaction instanceof PaymentTransaction ? $transaction->getCvvResult() : null,
            'paymentMethod'  => null,
            'remoteId'       => $transaction->getRemoteId(),
            'remoteParentId' => $transaction->getRemoteParentId(),
            'resultCode'     => $transaction->getResultCode(),
            'resultMessage'  => $transaction->getResultMessage(),
            'status'         => $transaction->getStatus() ? strtoupper($transaction->getStatus()->getName()) : null,
        ];

        if ($paymentMethod = $transaction->getPaymentMethod()) {
            $data['paymentMethod'] = [
                'kind'     => $paymentMethod instanceof CardPaymentMethod ? 'CARD' : null,
                'brand'    => $paymentMethod instanceof CardPaymentMethod && $paymentMethod->getBrand() ? strtoupper($paymentMethod->getBrand()->getName()) : null,
                'lastFour' => $paymentMethod->getLastFour(),
            ];
        }

        return $data;
    }

    /**
     * Prepares the given exception for an error response.
     *
     * @param Exception $exception
     *
     * @return WP_Error
     */
    protected function prepareError(Exception $exception) : WP_Error
    {
        return new WP_Error('mwc_payments_provider_processing_error', $exception->getMessage(), [
            'status' => $exception->getCode() ?: 400,
        ]);
    }

    /**
     * Checks if the current user can update items through the controller.
     *
     * @return bool
     */
    public function updateItemPermissionsCheck() : bool
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Gets a WooCommerce order for a given request.
     *
     * @param WP_REST_Request $request
     * @return WC_Order
     * @throws Exception
     */
    protected function getRequestWooCommerceOrder(WP_REST_Request $request) : WC_Order
    {
        $order = OrdersRepository::get((int) $request->get_param('orderId'));

        if (! $order) {
            throw new Exception(__('Invalid order ID.', 'mwc-core'), 400);
        }

        return $order;
    }

    /**
     * Gets a managed payment gateway for a given request.
     *
     * @param WP_REST_Request $request
     * @return AbstractPaymentGateway
     * @throws Exception
     */
    protected function getRequestManagedPaymentGateway(WP_REST_Request $request) : AbstractPaymentGateway
    {
        $providerName = StringHelper::sanitize($request->get_param('providerName'));
        $gateway = CorePaymentGateways::getManagedPaymentGatewayInstance($providerName);

        if (! $gateway) {
            throw new Exception(sprintf(
               /* translators: Placeholders: %s - a payment provider name, such as poynt */
                __('Provider %s not found.', 'mwc-core'),
                $providerName
            ), 404);
        }

        return $gateway;
    }

    /**
     * Gets the schema for REST processor items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [];
    }
}
