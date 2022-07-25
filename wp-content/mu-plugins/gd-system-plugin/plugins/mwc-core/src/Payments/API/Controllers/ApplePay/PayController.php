<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\ApplePay;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\Order\OrderAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\InitializesCartTrait;
use GoDaddy\WordPress\MWC\Core\Payments\DataSources\WooCommerce\Adapters\CartOrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller for submitting a payment with Apple Pay.
 */
class PayController extends AbstractController implements ConditionalComponentContract
{
    use InitializesCartTrait;

    /**
     * Sets the endpoint route.
     */
    public function __construct()
    {
        $this->route = 'payments/apple-pay';
    }

    /**
     * Loads the component and registers the endpoint routes.
     */
    public function load()
    {
        $this->registerRoutes();
    }

    /**
     * Registers the endpoint routes.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, '/'.$this->route.'/processing/pay', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'pay'],
                'permission_callback' => [$this, 'createItemPermissionsCheck'],
                'args'                => $this->getPayArgs(),
                'schema'              => [$this, 'getItemSchema'],
            ],
        ]);
    }

    /**
     * Gets the arguments for the pay endpoint.
     *
     * @return array
     */
    protected function getPayArgs() : array
    {
        return [
            'nonce' => [
                'required' => true,
                'type'     => 'string',
            ],
            'shouldTokenize' => [
                'required' => false,
                'type'     => 'boolean',
            ],
        ];
    }

    /**
     * Gets the payment request for the current customer.
     *
     * The logic of this method will set some POST variables that will be handled by the GoDaddy Payments gateway:
     * @see GoDaddyPaymentsGateway::getPaymentMethodForAdd() for the nonce
     * @see GoDaddyPaymentsGateway::getTransactionForPayment() for the tokenization flag
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function pay(WP_REST_Request $request)
    {
        try {
            $this->initializeCart();

            $nonce = sanitize_text_field((string) $request->get_param('nonce'));

            if (empty($nonce) || ! is_string($nonce)) {
                throw new Exception('Missing nonce');
            }

            /* @var GoDaddyPaymentsGateway $gateway */
            $gateway = CorePaymentGateways::getManagedPaymentGatewayInstance('poynt');
            $orderId = $this->createOrderFromCart();

            $_POST['mwc-payments-poynt-payment-nonce'] = $nonce;
            $_POST['mwc-payments-poynt-tokenize-payment-method'] = (bool) $request->get_param('shouldTokenize');

            $responseData = $gateway->process_payment($orderId);
            $redirectUrl = ArrayHelper::get($responseData, 'redirect');

            if (! $redirectUrl || 'success' !== ArrayHelper::get($responseData, 'result')) {
                throw new Exception(ArrayHelper::get($responseData, 'message') ?? 'Unknown error');
            }

            $response = [
                'orderId'     => $orderId,
                'redirectUrl' => $redirectUrl,
            ];
        } catch (Exception $exception) {
            $response = new WP_Error('PAYMENT_FAILED', $exception->getMessage(), [
                'status' => $exception->getCode() ?: 400,
                'field' => null,
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Creates an order from the cart.
     *
     * @return int order ID
     * @throws Exception
     */
    protected function createOrderFromCart() : int
    {
        $order = $this->getCartOrder();
        $wcOrder = $this->getSourceOrder($order);

        return (int) $wcOrder->save();
    }

    /**
     * Gets a WooCommerce order from a native order.
     *
     * @param Order $order
     * @return WC_Order
     * @throws Exception
     */
    protected function getSourceOrder(Order $order) : WC_Order
    {
        return (new OrderAdapter(new WC_Order()))->convertToSource($order);
    }

    /**
     * Gets an order from the cart.
     *
     * @return Order
     * @throws Exception
     */
    protected function getCartOrder() : Order
    {
        if (! WC()->cart) {
            throw new Exception('WooCommerce cart is unavailable', 500);
        }

        return (new CartOrderAdapter(WC()->cart))->convertFromSource([], ['created_via' => 'mwc_payments_apple_pay']);
    }

    /**
     * Gets the item schema.
     *
     * @internal
     *
     * @return array
     */
    public function getItemSchema(): array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'status',
            'type'       => 'object',
            'properties' => [
                'orderId'     => [
                    'description' => __('The order ID.', 'mwc-core'),
                    'type'        => 'integer',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'redirectUrl' => [
                    'description' => __('The URL to redirect the customer to.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }

    /**
     * Determines whether the component should load.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoad(): bool
    {
        return ApplePayGateway::isActive();
    }
}
