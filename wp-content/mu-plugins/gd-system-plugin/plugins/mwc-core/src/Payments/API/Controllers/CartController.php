<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\ProductsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\API\Traits\InitializesCartTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use WC_Coupon;
use WC_Discounts;
use WC_Product;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Cart controller.
 */
class CartController extends AbstractController implements ConditionalComponentContract
{
    use InitializesCartTrait;

    /**
     * Sets the endpoint route.
     */
    public function __construct()
    {
        $this->route = 'cart';
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
        register_rest_route($this->namespace, '/'.$this->route, [
            [
                'methods'             => 'PATCH',
                'callback'            => [$this, 'updateCart'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
                'args'                => $this->getUpdateCartArgs(),
                'schema'              => [$this, 'getItemSchema'],
            ],
        ]);
    }

    /**
     * Gets the arguments for the update cart endpoint.
     *
     * @return array
     */
    protected function getUpdateCartArgs() : array
    {
        return [
            'couponCode' => [
                'required' => false,
                'type'     => 'string',
            ],
            'customer'   => [
                'required'   => false,
                'type'       => 'object',
                'properties' => [
                    'billingAddress' => [
                        'type'       => 'object',
                        'properties' => [
                            'businessName'            => [
                                'type' => 'string',
                            ],
                            'firstName'               => [
                                'type' => 'string',
                            ],
                            'lastName'                => [
                                'type' => 'string',
                            ],
                            'lines'                   => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'locality'                => [
                                'type' => 'string',
                            ],
                            'subLocalities'           => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'administrativeDistricts' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'postalCode'              => [
                                'type' => 'string',
                            ],
                            'countryCode'             => [
                                'type' => 'string',
                            ],
                            'phone'                   => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'shippingAddress' => [
                        'type'       => 'object',
                        'properties' => [
                            'businessName'            => [
                                'type' => 'string',
                            ],
                            'firstName'               => [
                                'type' => 'string',
                            ],
                            'lastName'                => [
                                'type' => 'string',
                            ],
                            'lines'                   => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'locality'                => [
                                'type' => 'string',
                            ],
                            'subLocalities'           => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'administrativeDistricts' => [
                                'type'  => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'postalCode'              => [
                                'type' => 'string',
                            ],
                            'countryCode'             => [
                                'type' => 'string',
                            ],
                            'phone'                   => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'shippingMethod'                  => [
                        'type' => 'string',
                    ],
                ],
            ],
            'products'   => [
                'required'   => false,
                'properties' => [
                    'attributes' => [
                        'type'  => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    'id'         => [
                        'type' => 'integer',
                    ],
                    'quantity'   => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ];
    }

    /**
     * Updates the cart with the given data from a request.
     *
     * @internal
     *
     * @param WP_REST_Request $request
     * @return WP_Rest_Response|WP_Error
     * @throws Exception
     */
    public function updateCart(WP_REST_Request $request)
    {
        $this->initializeCart();

        $couponCode = $this->maybeApplyCouponCode($request);
        $customerData = $this->setCustomerData($request);
        $productsData = $this->addProducts($request);

        // TODO: switch to throwing & catching a custom exception
        foreach ([$couponCode, $customerData, $productsData] as $responseProperty) {
            if (is_a($responseProperty, 'WP_Error')) {
                return rest_ensure_response($responseProperty);
            }
        }

        return rest_ensure_response([
            'couponCode' => $couponCode,
            'customer'   => $customerData,
            'products'   => $productsData,
        ]);
    }

    /**
     * Applies a coupon code to the cart if present in the request and coupons are enabled.
     *
     * @param WP_REST_Request $request
     *
     * @return string|null|WP_Error
     * @throws Exception
     */
    protected function maybeApplyCouponCode(WP_REST_Request $request)
    {
        $couponCode = sanitize_text_field((string) $request->get_param('couponCode'));

        if (! $couponCode) {
            return null;
        }

        if (! wc_coupons_enabled()) {
            return $this->getUpdateCartResponseError(__('Coupons are disabled.', 'mwc-core'), 'COUPONS_DISABLED', 'couponCode');
        }

        return $this->applyCouponCode($couponCode);
    }

    /**
     * Applies the given coupon code to the cart.
     *
     * @param string $couponCode
     *
     * @return string|WP_Error
     * @throws Exception
     */
    protected function applyCouponCode(string $couponCode)
    {
        $coupon = $this->getCoupon($couponCode);

        if ($coupon->get_code() !== $couponCode) {
            return $this->getUpdateCartResponseError(__('Invalid coupon code.', 'mwc-core'), 'INVALID_COUPON_CODE', 'couponCode');
        }

        $isValid = $this->validateCoupon($coupon);

        // return the WP_Error
        if (true !== $isValid) {
            return $isValid;
        }

        if (! $this->getCartInstance()->add_discount($couponCode)) {
            return $this->getUpdateCartResponseError(__('Coupon cannot be applied.', 'mwc-core'), 'INVALID_COUPON_CODE', 'couponCode');
        }

        return $coupon->get_code();
    }

    /**
     * Validates a given coupon.
     *
     * @param WC_Coupon $coupon
     * @return true|WP_Error
     * @throws Exception
     */
    protected function validateCoupon(WC_Coupon $coupon)
    {
        $isValid = $this->getDiscountsInstance()->is_coupon_valid($coupon);

        if ($isValid instanceof WP_Error) {
            return $this->getUpdateCartResponseError(sprintf(
                __('Coupon cannot be applied. %s', 'mwc-core'),
                $isValid->get_error_message()
            ), 'INVALID_COUPON_CODE', 'couponCode');
        }

        return true;
    }

    /**
     * Gets the WooCommerce discounts handler instance.
     *
     * @return WC_Discounts
     * @throws Exception
     */
    protected function getDiscountsInstance() : WC_Discounts
    {
        return new WC_Discounts($this->getCartInstance());
    }

    /**
     * Gets a WooCommerce coupon instance.
     *
     * @param string $couponCode
     * @return WC_Coupon
     */
    protected function getCoupon(string $couponCode) : WC_Coupon
    {
        return new WC_Coupon($couponCode);
    }

    /**
     * Gets the customer data for the request.
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error|null
     */
    protected function setCustomerData(WP_REST_Request $request)
    {
        $customerData = $request->get_param('customer');

        if (empty($customerData)) {
            return null;
        }

        $errorMessage = __('Invalid customer data', 'mwc-core');
        $errorCode = 'UNKNOWN';

        if (! ArrayHelper::accessible($customerData) || ! ArrayHelper::isAssoc($customerData)) {
            return $this->getUpdateCartResponseError($errorMessage, $errorCode);
        }

        $billingAddress = $this->setCustomerAddress('billing', $customerData);
        $emailAddress = $this->setCustomerEmailAddress($customerData);
        $shippingAddress = $this->setCustomerAddress('shipping', $customerData);
        $shippingMethod = $this->setShippingMethod($customerData);

        foreach ([$billingAddress, $emailAddress, $shippingAddress, $shippingMethod] as $possibleError) {
            if (is_a($possibleError, 'WP_Error')) {
                return $possibleError;
            }
        }

        return [
            'billingAddress'  => $billingAddress,
            'emailAddress'    => $emailAddress,
            'shippingAddress' => $shippingAddress,
            'shippingMethod'  => $shippingMethod,
        ];
    }

    /**
     * Sets the customer email address in session.
     *
     * @param array $customerData
     * @return string|null|WP_Error
     */
    protected function setCustomerEmailAddress(array $customerData)
    {
        $emailAddress = ArrayHelper::get($customerData, 'emailAddress');
        $emailAddress = is_string($emailAddress) ? sanitize_text_field($emailAddress) : null;
        $isValidEmail = ValidationHelper::isEmail($emailAddress);

        if (null !== $emailAddress && ! $isValidEmail) {
            return $this->getUpdateCartResponseError(__('Invalid email address', 'mwc-core'), 'INVALID_BILLING_CONTACT', 'EMAIL');
        } elseif ($isValidEmail) {
            $wc = WooCommerceRepository::getInstance();

            if ($wc && isset($wc->customer) && is_callable([$wc->customer, 'set_billing_email'])) {
                $wc->customer->set_billing_email($emailAddress);
            }
        }

        return $emailAddress;
    }

    /**
     * Sets the customer address to session.
     *
     * @param string $which either 'billing' or 'shipping'
     * @param array $customerData
     * @return array|null|WP_Error
     */
    protected function setCustomerAddress(string $which, array $customerData)
    {
        $addressData = ArrayHelper::get($customerData, "{$which}Address", []);

        if (empty($addressData) || ('billing' !== $which && 'shipping' !== $which)) {
            return null;
        }

        /* translators: Placeholder: %s - either 'billing' or 'shipping' */
        $errorMessage = sprintf(__('Invalid %s address', 'mwc-core'), $which);
        $errorCode = 'INVALID_'.strtoupper($which).'_CONTACT';

        if (! ArrayHelper::accessible($addressData) || ! ArrayHelper::isAssoc($addressData)) {
            return $this->getUpdateCartResponseError($errorMessage, $errorCode);
        }

        $address = $this->getAdaptedAddress($addressData);
        $validatedAddress = [];

        foreach ($address as $part => $value) {
            $value = is_string($value) ? sanitize_text_field($value) : null;
            $hasError = empty($value);

            // validate mandatory non-empty fields
            switch ($part) {
                case 'first_name':
                case 'last_name':
                    $errorField = 'NAME';
                    break;
                case 'address_1':
                    $errorField = 'ADDRESS_LINES';
                    break;
                case 'city':
                    $errorField = 'LOCALITY';
                    break;
                case 'postcode':
                    $errorField = 'POSTAL_CODE';
                    break;
                case 'country':
                    $errorField = 'COUNTRY_CODE';
                    break;
                default:
                    $errorField = null;
                    $hasError = false;
            }

            if ($hasError && ! empty($errorField)) {
                return $this->getUpdateCartResponseError($errorMessage, $errorCode, $errorField);
            }

            $validatedAddress[$part] = $value;
        }

        if (! $this->validateCountryState($validatedAddress['country'], $validatedAddress['state'])) {
            return $this->getUpdateCartResponseError($errorMessage, $errorCode, 'ADMINISTRATIVE_AREA');
        }

        if ('shipping' === $which && ! $this->validateShippingCountry($validatedAddress['country'])) {
            return $this->getUpdateCartResponseError($errorMessage, 'UNSERVICABLE_ADDRESS', 'ADDRESS');
        }

        $wc = WooCommerceRepository::getInstance();

        foreach ($validatedAddress as $part => $value) {
            $method = "set_{$which}_{$part}";

            if ($wc && isset($wc->customer) && is_callable([$wc->customer, $method])) {
                $wc->customer->$method($value);
            }
        }

        return $validatedAddress;
    }

    /**
     * Validates a country for shipping.
     *
     * @param string $country
     * @return bool
     */
    protected function validateShippingCountry(string $country) : bool
    {
        $wc = WooCommerceRepository::getInstance();
        $countries = $wc && isset($wc->countries) && is_callable([$wc->countries, 'get_shipping_countries']) ? $wc->countries->get_shipping_countries() : [];

        return ArrayHelper::exists($countries, $country);
    }

    /**
     * Validates that a state is valid for a given country.
     *
     * @param string $country
     * @param string $state
     * @return bool
     */
    protected function validateCountryState(string $country, string $state) : bool
    {
        $wc = WooCommerceRepository::getInstance();
        $states = $wc && isset($wc->countries) && is_callable([$wc->countries, 'get_states']) ? $wc->countries->get_states($country) : [];

        return '' === $state && empty($states) || ArrayHelper::exists($states, $state);
    }

    /**
     * Gets an address adapted from the given address data converted to a WooCommerce address.
     *
     * @param array $addressData
     * @return array
     */
    protected function getAdaptedAddress(array $addressData) : array
    {
        $address = (new Address())->setProperties($addressData);

        return (new AddressAdapter([]))->convertToSource($address);
    }

    /**
     * Sets the shipping method to the current customer session.
     *
     * @param array $customerData
     * @return string|null
     */
    protected function setShippingMethod(array $customerData)
    {
        $shippingMethod = ArrayHelper::get($customerData, 'shippingMethod');
        $shippingMethod = is_string($shippingMethod) ? sanitize_text_field($shippingMethod) : null;

        if (! empty($shippingMethod)) {
            $wc = WooCommerceRepository::getInstance();
            if ($wc && isset($wc->session) && is_callable([$wc->session, 'set'])) {
                $wc->session->set('chosen_shipping_methods', [$shippingMethod]);
            }
        }

        return $shippingMethod;
    }

    /**
     * Gets the product data for the request.
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error
     * @throws Exception
     */
    protected function addProducts(WP_REST_Request $request)
    {
        $products = $request->get_param('products');

        if (empty($products)) {
            return [];
        }

        if (! ArrayHelper::accessible($products)) {
            return $this->getUpdateCartResponseError(__('Invalid products data.', 'mwc-core'), 'UNKNOWN');
        }

        $validatedProducts = [];

        foreach ($products as $productData) {
            if (! ArrayHelper::exists($productData, 'attributes') || ! ArrayHelper::exists($productData, 'id') || ! ArrayHelper::exists($productData, 'quantity')) {
                continue;
            }

            if (! is_numeric($productData['id']) || ! is_numeric($productData['quantity']) || ! ArrayHelper::accessible($productData['attributes'])) {
                continue;
            }

            $product = ProductsRepository::get((int) $productData['id']);

            // there is no need to check if the product is purchasable or in stock as the add to cart function from WooCommerce will take care of that, and produce any notice in the front end
            if (! $product) {
                continue;
            }

            if ($addedProductData = $this->addProduct($product, (float) $productData['quantity'], wc_clean($productData['attributes']))) {
                $validatedProducts[] = $addedProductData;
            }
        }

        return $validatedProducts;
    }

    /**
     * Adds a product to the cart.
     *
     * @param WC_Product $product
     * @param float $quantity
     * @param array $attributes
     * @return array|null
     * @throws Exception
     */
    protected function addProduct(WC_Product $product, float $quantity, array $attributes = [])
    {
        $cart = $this->getCartInstance();

        if ($parentId = $product->get_parent_id()) {
            $productId = $parentId;
            $variationId = $addedProductId = $product->get_id();
        } else {
            $productId = $addedProductId = $product->get_id();
            $variationId = 0;
        }

        // WooCommerce will handle any validation about whether the product can actually be added to cart (if it's purchasable, in stock, etc.) and output notices in the front end if not
        $added = $cart->add_to_cart($productId, $quantity, $variationId, $attributes);

        return ! $added ? null : [
            'attributes' => $attributes,
            'id'         => $addedProductId,
            'quantity'   => $quantity,
        ];
    }

    /**
     * Formats a response error for updating the cart.
     *
     * @param string $message
     * @param string $code
     * @param string|null $field optional
     * @return WP_Error
     */
    protected function getUpdateCartResponseError(string $message, string $code, $field = null) : WP_Error
    {
        return new WP_Error($code, $message,
            [
                'status' => 400,
                'field'  => $field,
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
    public function getItemSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'status',
            'type' => 'object',
            'properties' => [
                'couponCode' => [
                    'description' => __('Coupon code to apply to the cart.', 'mwc-core'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'customer' => [
                    'description' => __('Cart customer data.', 'mwc-core'),
                    'type' => 'object',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                    'properties' => [
                        'billingAddress' => [
                            'type' => 'object',
                            'properties' => [
                                'businessName' => [
                                    'type' => 'string',
                                ],
                                'firstName' => [
                                    'type' => 'string',
                                ],
                                'lastName' => [
                                    'type' => 'string',
                                ],
                                'lines' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'locality' => [
                                    'type' => 'string',
                                ],
                                'subLocalities' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'administrativeDistricts' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'postalCode' => [
                                    'type' => 'string',
                                ],
                                'countryCode' => [
                                    'type' => 'string',
                                ],
                                'phone' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        'emailAddress' => [
                            'type' => 'string',
                        ],
                        'shippingAddress' => [
                            'type' => 'object',
                            'properties' => [
                                'businessName' => [
                                    'type' => 'string',
                                ],
                                'firstName' => [
                                    'type' => 'string',
                                ],
                                'lastName' => [
                                    'type' => 'string',
                                ],
                                'lines' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'locality' => [
                                    'type' => 'string',
                                ],
                                'subLocalities' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'administrativeDistricts' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'postalCode' => [
                                    'type' => 'string',
                                ],
                                'countryCode' => [
                                    'type' => 'string',
                                ],
                                'phone' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
                'shippingMethod' => [
                    'type' => 'string',
                ],
                'products' => [
                    'description' => __('Products to add to the cart.', 'mwc-core'),
                    'type' => 'array',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'attributes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'id' => [
                                'type' => 'integer',
                            ],
                            'quantity' => [
                                'type' => 'float',
                            ],
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
