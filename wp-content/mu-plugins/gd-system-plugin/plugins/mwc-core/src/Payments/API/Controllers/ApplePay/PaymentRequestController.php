<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\ApplePay;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\InitializesCartTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use WC_Countries;
use WC_Product;
use WC_Session;
use WP_Error;
use WP_REST_Response;

/**
 * Payment request controller.
 */
class PaymentRequestController extends AbstractController implements ConditionalComponentContract
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
        register_rest_route($this->namespace, '/'.$this->route.'/payment-request', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getPaymentRequest'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                'schema'              => [$this, 'getItemSchema'],
            ],
        ]);
    }

    /**
     * Gets the chosen shipping methods.
     *
     * @TODO: consider using a MWC Common repository method, see MWC-3651 {astandiford}
     *
     * @return string[] list of chosen shipping methods
     * @throws Exception
     */
    protected function getChosenShippingMethods() : array
    {
        $wc = WooCommerceRepository::getInstance();

        if (! $wc || empty($wc->session) || ! $wc->session instanceof WC_Session) {
            throw new Exception(__('WooCommerce session handler is not available', 'mwc-core'));
        }

        return ArrayHelper::wrap($wc->session->get('chosen_shipping_methods', []));
    }

    /**
     * Gets the WooCommerce countries handler.
     *
     * @TODO: consider using a MWC Common repository method, see MWC-3651 {astandiford}
     *
     * @return WC_Countries
     * @throws Exception
     */
    protected function getCountries() : WC_Countries
    {
        $wc = WooCommerceRepository::getInstance();

        if (! $wc || empty($wc->countries) || ! $wc->countries instanceof WC_Countries) {
            throw new Exception(__('WooCommerce countries handler is not available', 'mwc-core'));
        }

        return $wc->countries;
    }

    /**
     * Gets all billing address fields.
     *
     * @TODO: consider using a MWC Common repository method, see MWC-3651 {astandiford}
     *
     * @return array[] list of billing address field data
     * @throws Exception
     */
    protected function getBillingAddressFields() : array
    {
        return ArrayHelper::wrap($this->getCountries()->get_address_fields('', 'billing_'));
    }

    /**
     * Gets all shipping address fields.
     *
     * @TODO: consider using a MWC Common repository method, see MWC-3651 {astandiford}
     *
     * @return array[] list of shipping address field data
     * @throws Exception
     */
    protected function getShippingAddressFields() : array
    {
        return ArrayHelper::wrap($this->getCountries()->get_address_fields('', 'shipping_'));
    }

    /**
     * Gets the required billing fields.
     *
     * @return string[] list of required billing field identifiers
     * @throws Exception
     */
    protected function getRequiredBillingFields() : array
    {
        return $this->getRequiredAddressFields($this->getBillingAddressFields(), 'billing');
    }

    /**
     * Retrieves the required shipping fields.
     *
     * @return string[] list of required shipping field identifiers
     * @throws Exception
     */
    protected function getRequiredShippingFields() : array
    {
        return $this->getRequiredAddressFields($this->getShippingAddressFields(), 'shipping');
    }

    /**
     * Gets the required fields, given the type.
     *
     * @param array[] $addressFields list of associative arrays containing a required field, keyed by the field type
     * @param string $addressType the field type - "shipping" or "billing"
     * @return string[] list of required field identifiers (ADDRESS, EMAIL, NAME, or PHONE)
     */
    protected function getRequiredAddressFields(array $addressFields, string $addressType) : array
    {
        $normalizedTypes = [
            $addressType.'_first_name' => 'NAME',
            $addressType.'_last_name'  => 'NAME',
            $addressType.'_address'    => 'ADDRESS',
            $addressType.'_address_1'  => 'ADDRESS',
            $addressType.'_address_2'  => 'ADDRESS',
            $addressType.'_email'      => 'EMAIL',
            $addressType.'_phone'      => 'PHONE',
        ];

        $requiredFields = [];

        foreach ($addressFields as $key => $field) {
            if (isset($normalizedTypes[$key], $field['required']) && true === $field['required']) {
                $requiredFields[] = $normalizedTypes[$key];
            }
        }

        return array_unique($requiredFields);
    }

    /**
     * Gets a list of available shipping method identifiers for this payment request.
     *
     * Apple Pay identifiers can be PICKUP, DELIVERY, or SHIPPING.
     *
     * @return string[] list of shipping method identifiers
     * @throws Exception
     */
    protected function getShippingMethods() : array
    {
        $shippingMethods = [];

        foreach ($this->getChosenShippingMethods() as $shippingMethod) {
            $shippingMethod = StringHelper::before($shippingMethod, ':');

            if (ArrayHelper::contains(['local_pickup', 'local_pickup_plus'], $shippingMethod)) {
                $shippingMethods[] = 'PICKUP';
            } elseif ('mwc_local_delivery' === $shippingMethod) {
                $shippingMethods[] = 'DELIVERY';
            } else {
                $shippingMethods[] = 'SHIPPING';
            }
        }

        // Reset array keys. Prevents the JSON response from unexpectedly creating an object instead of an array.
        return array_values(array_unique($shippingMethods));
    }

    /**
     * Gets the shipping type for an order.
     *
     * @return string|null
     * @throws Exception
     */
    protected function getShippingType()
    {
        return ArrayHelper::get($this->getShippingMethods(), 0, null);
    }

    /**
     * Gets the line items, formatted for this payment request.
     *
     * @return array[] list of line amounts, and labels
     * @throws Exception
     */
    protected function getLineItems() : array
    {
        $result = [];

        foreach ($this->getCartItems() as $lineItem) {
            $product = $lineItem['data'] ?? null;

            if (! $product instanceof WC_Product) {
                continue;
            }

            $result[] = [
                'amount' => (float) ($lineItem['line_subtotal'] ?? 0),
                'label'  => $product->get_name(),
            ];
        }

        return $result;
    }

    /**
     * Gets the cart total.
     *
     * @return float the total price in the cart
     * @throws Exception
     */
    protected function getTotal() : float
    {
        return (float) $this->getCartInstance()->get_cart_contents_total();
    }

    /**
     * Gets the items from the cart.
     *
     * @return array[] the session's cart items
     * @throws Exception
     */
    protected function getCartItems() : array
    {
        return ArrayHelper::wrap($this->getCartInstance()->get_cart());
    }

    /**
     * Gets the payment request for the current customer.
     *
     * @internal
     *
     * @return WP_REST_Response|WP_Error
     */
    public function getPaymentRequest()
    {
        try {
            $this->initializeCart();

            $response = [
                'allowCoupons'           => wc_coupons_enabled(), // @TODO consider using a MWC Common repository method, see MWC-3651 {astandiford}
                'currencyCode'           => WooCommerceRepository::getCurrency(),
                'requiredBillingFields'  => $this->getRequiredBillingFields(),
                'requiredShippingFields' => $this->getRequiredShippingFields(),
                'shippingMethods'        => $this->getShippingMethods(),
                'shippingType'           => $this->getShippingType(),
                'merchantName'           => SiteRepository::getTitle(),
                'countryCode'            => WooCommerceRepository::getBaseCountry(),
                'lineItems'              => $this->getLineItems(),
                'total'                  => [
                    'amount' => $this->getTotal(),
                    'label'  => SiteRepository::getTitle(),
                ],
            ];
        } catch (Exception $exception) {
            $response = $this->getPaymentRequestError($exception);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets a payment request error.
     *
     * @param Exception $exception
     * @return WP_Error
     */
    protected function getPaymentRequestError(Exception $exception) : WP_Error
    {
        return new WP_Error('UNKNOWN', $exception->getMessage(),
            [
                'status' => 500,
                'field'  => null,
            ]
        );
    }

    /**
     * Gets the item schema.
     *
     * @internal
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'status',
            'type' => 'object',
            'properties' => [
                'allowCoupons' => [
                    'description' => __('Whether the customer should be allowed to enter coupons.', 'mwc-core'),
                    'type' => 'boolean',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'countryCode' => [
                    'description' => __('2-letter ISO 3166 country code.', 'mwc-core'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'currencyCode'           => [
                    'description' => __('3-letter ISO 4217 currency code.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'lineItems'              => [
                    'description' => __('Items in the order.', 'mwc-core'),
                    'type'        => 'array',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'amount' => [
                                'type'  => 'float',
                            ],
                            'label'  => [
                                'type'  => 'string',
                            ],
                        ],
                    ],
                ],
                'merchantName'           => [
                    'description' => __('Name of the merchant.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'requiredBillingFields'  => [
                    'description' => __('Required billing fields.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'enum'        => [
                        'ADDRESS',
                        'EMAIL',
                        'NAME',
                        'PHONE',
                    ],
                ],
                'requiredShippingFields' => [
                    'description' => __('Required shipping fields.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'enum'        => [
                        'ADDRESS',
                        'EMAIL',
                        'NAME',
                        'PHONE',
                    ],
                ],
                'shippingType'           => [
                    'description' => __('The shipping type based on the chosen shipping method.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'enum'        => [
                        'DELIVERY',
                        'PICKUP',
                        'SHIPPING',
                    ],
                ],
                'shippingMethods'        => [
                    'description'  => __('Shipping methods for the payment request.', 'mwc-core'),
                    'type'         => 'array',
                    'context'      => ['view', 'edit'],
                    'readonly'     => true,
                    'items'        => [
                        'type' => 'string',
                    ],
                ],
                'total'                  => [
                    'description' => __('Order total, based on cart total.', 'mwc-core'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'properties'  => [
                        'amount' => [
                            'type'  => 'float',
                        ],
                        'label'  => [
                            'type'  => 'string',
                        ],
                    ],
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
    public static function shouldLoad() : bool
    {
        return ApplePayGateway::isActive();
    }
}
