<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\LocalDelivery;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Eval_Math;
use WC_Shipping_Method;

/**
 * Integration class for the Local Delivery shipping method.
 *
 * @since 2.14.0
 */
class LocalDelivery extends WC_Shipping_Method
{
    /**
     * Cost passed to [fee] shortcode.
     *
     * @since 2.14.0
     *
     * @var string Cost.
     */
    protected $feeCost = '';

    /** @var string Cost */
    protected $cost;

    /** @var string */
    protected $checkout_description;

    /** @var string */
    protected $order_received_instruction;

    /**
     * Min amount to be valid.
     *
     * @var float
     */
    public $min_amount = 0;

    /** @var float string Minimum order total when free */
    protected $free_amount = 0;

    /**
     * The ID of the shipping method.
     *
     * @var string
     */
    public $id = 'mwc_local_delivery';

    /**
     * @var string[]
     */
    public $supports = [
        'shipping-zones',
        'instance-settings',
        'instance-settings-modal',
    ];

    /**
     * LocalDelivery constructor.
     *
     * @param int $instance_id
     *
     * @since 2.14.0
     */
    public function __construct(int $instance_id = 0)
    {
        $this->method_title = __('Local delivery', 'mwc-core');
        $this->method_description = __('Allow customers to select local delivery service. Taxes will be applied using customer\'s shipping address.', 'mwc-core');
        $this->instance_id = abs($instance_id);

        $this->init();
    }

    /**
     * Initialize the settings.
     *
     * @throws Exception
     * @since 2.14.0
     */
    public function init()
    {
        // Load the settings
        $this->instance_form_fields = $this->getSettingsFields();

        // load the settings
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->tax_status = $this->get_option('tax_status');
        $this->cost = $this->get_option('cost');
        $this->checkout_description = $this->get_option('checkout_description');
        $this->order_received_instruction = $this->get_option('order_received_instruction');
        $this->min_amount = $this->get_option('min_amount', 0);
        $this->free_amount = $this->get_option('free_amount');

        // Save settings in admin if you have any defined
        Register::action()
            ->setGroup("woocommerce_update_options_shipping_{$this->id}")
            ->setHandler([$this, 'process_admin_options'])
            ->execute();
    }

    /**
     * Returns a rate ID based on this methods ID and instance, with an optional
     * suffix if distinguishing between multiple rates.
     *
     * @since 2.14.0
     * @param string $suffix Suffix.
     * @return string
     */
    public function getRateId(string $suffix = ''): string
    {
        $rate_ids = [$this->id];

        if ($this->instance_id) {
            $rate_ids[] = $this->instance_id;
        }

        if ($suffix) {
            $rate_ids[] = $suffix;
        }

        return implode(':', $rate_ids);
    }

    /**
     * Local delivery setting fields.
     *
     * @since 2.14.0
     * @return array
     */
    public function getSettingsFields(): array
    {
        $cost_desc = __('Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'mwc-core').'<br/><br/>'.__('Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'mwc-core');

        $formFields = [
            'title'      => [
                'title'       => __('Checkout Title', 'mwc-core'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mwc-core'),
                'default'     => __('Local delivery', 'mwc-core'),
                'desc_tip'    => true,
            ],
            'tax_status' => [
                'title'   => __('Tax status', 'mwc-core'),
                'type'    => 'select',
                'class'   => 'wc-enhanced-select',
                'default' => 'taxable',
                'options' => [
                    'taxable' => __('Taxable', 'mwc-core'),
                    'none'    => _x('None', 'Tax status', 'mwc-core'),
                ],
            ],
            'cost'       => [
                'title'       => __('Cost', 'mwc-core'),
                'type'        => 'text',
                'placeholder' => '0',
                'description' => $cost_desc,
                'default'     => '0',
                'desc_tip'    => true,
            ],
            'min_amount' => [
                'title'       => __('Minimum order amount total when available', 'mwc-core'),
                'type'        => 'price',
                'placeholder' => '0',
                'description' => __('The amount a customer’s order must be greater than or equal to in order to select the shipping method.', 'mwc-core'),
                'default'     => '0',
                'desc_tip'    => true,
            ],
            'free_amount' => [
                'title'       => __('Minimum order total when free', 'mwc-core'),
                'type'        => 'price',
                'placeholder' => '0',
                'description' => __('The amount a customer’s order must be greater than or equal to for the shipping method to be free.', 'mwc-core'),
                'default'     => '0',
                'desc_tip'    => true,
            ],
            'checkout_description' => [
                'title'       => __('Checkout description', 'mwc-core'),
                'type'        => 'textarea',
                'placeholder' => __('Checkout description', 'mwc-core'),
                'description' => __('This description will be displayed underneath the Local Delivery shipping method name in the Cart and Checkout pages.', 'mwc-core'),
                'default'     => '',
                'desc_tip'    => true,
            ],
            'order_received_instruction' => [
                'title'       => __('Order received instructions', 'mwc-core'),
                'type'        => 'textarea',
                'placeholder' => __('Delivery instructions', 'mwc-core'),
                'description' => __('Message that the customer will see on the order received page and the processing order email after checkout.', 'mwc-core'),
                'default'     => '',
                'desc_tip'    => true,
            ],
        ];

        /*
         * Filter Local Delivery shipping method settings fields.
         *
         * @since 2.14.0
         *
         * @param array $form_fields settings fields
         */
        return (array) apply_filters('mwc_local_delivery_settings', $formFields);
    }

    /**
     * Get items in package.
     *
     * @param  array $package Package of items from cart.
     * @return int
     */
    public function getPackageItemQty(array $package = []): int
    {
        $totalQuantity = 0;

        foreach (ArrayHelper::get($package, 'contents', []) as $values) {
            $quantity = ArrayHelper::get($values, 'quantity', 0);
            $data = ArrayHelper::get($values, 'data', false);

            if ($quantity > 0 && $data && $data->needs_shipping()) {
                $totalQuantity += $quantity;
            }
        }

        return $totalQuantity;
    }

    /**
     * Calculate local delivery shipping.
     *
     * @param array $package Package information.
     * @throws Exception
     * @since 2.14.0
     */
    public function calculate_shipping($package = [])
    {
        $rate = [
            'id'      => $this->getRateId(),
            'label'   => $this->title,
            'cost'    => $this->cost,
            'package' => $package,
        ];

        // @NOTE: Bail if Woo not enabled and this method was called for some reason {JO: 2021-09-16}
        if (WooCommerceRepository::isWooCommerceActive()) {
            $total = WC()->cart->get_displayed_subtotal();
            $total = $this->formatPrice($total);

            $rate['cost'] = $this->determineShippingRate($total, $package);

            $this->add_rate($rate);
        }

        /*
         * Developers can add additional developer rates.
         *
         * @since 2.14.0
         *
         * @param object shipping method.
         * @param array current shipping rate for local delivery.
         */
        do_action('woocommerce_'.$this->id.'_shipping_add_rate', $this, $rate);
    }

    /**
     * Calculate local delivery shipping.
     *
     * @param float|int $total The current total cost
     * @param array $package Package information.
     *
     * @return float|int
     */
    protected function determineShippingRate($total, array $package = [])
    {
        if (! empty($this->free_amount) && $total >= $this->free_amount) {
            return 0;
        }

        if (! empty($this->cost)) {
            return $this->evaluateCost($this->cost, [
                'qty'  => $this->getPackageItemQty($package),
                'cost' => ArrayHelper::get($package, 'contents_cost'),
            ]);
        }

        return $this->cost;
    }

    /**
     * Calculate the cost.
     *
     * @param int|string $sum
     * @param array $details
     * @return int
     * @throws Exception
     */
    public function evaluateCost($sum, array $details = []): int
    {
        // Add warning for subclasses.
        if (! ArrayHelper::has($details, ['qty', 'cost'])) {
            // @TODO: Feels like this might be better as a sentry error and behind the scenes {JO: 2021-09-16}
            wc_doing_it_wrong(__FUNCTION__, '$details must contain `cost` and `qty` keys.', '4.0.1');
        }

        include_once WC()->plugin_path().'/includes/libraries/class-wc-eval-math.php';

        $details = apply_filters('woocommerce_evaluate_shipping_cost_args', $details, $sum, $this);
        $this->feeCost = ArrayHelper::get($details, 'cost');

        // Expand shortcodes.
        $sum = $this->executeFeeShortcode($sum, $details);

        // Sanitize and cleanup sum
        $sum = $this->sanitizeSum($sum);

        // Do the math.
        return $sum ? WC_Eval_Math::evaluate($sum) : 0;
    }

    /**
     * Execute Fee Shortcode.
     *
     * @param int|string $sum
     * @param array $details
     * @return string|null
     */
    protected function executeFeeShortcode($sum, array $details = [])
    {
        // Expand shortcodes.
        add_shortcode('fee', [$this, 'fee']);

        $sum = do_shortcode(str_replace(['[qty]', '[cost]'], [$details['qty'], $details['cost']], $sum));

        remove_shortcode('fee');

        return $sum;
    }

    /**
     * Cleanup and remove character from sum string.
     *
     * @param string|null $sum
     *
     * @return string|null
     * @throws Exception
     */
    protected function sanitizeSum(string $sum = '')
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return $sum;
        }

        $locale = localeconv();
        $decimals = [wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ','];

        // Remove whitespace from string.
        $sum = preg_replace('/\s+/', '', $sum);

        // Remove locale from string.
        $sum = str_replace($decimals, '.', $sum);

        // Trim invalid start/end characters.
        return rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");
    }

    /**
     * Work out fee (shortcode).
     *
     * @param $attributes
     * @return string
     */
    public function fee($attributes)
    {
        $attributes = shortcode_atts(['percent' => '', 'min_fee' => '', 'max_fee' => ''], $attributes, 'fee');
        $calculatedFee = 0;

        if ($percent = ArrayHelper::get($attributes, 'percent')) {
            $calculatedFee = $this->feeCost * (floatval($percent / 100));
        }

        if (! empty($minFee = ArrayHelper::get($attributes, 'min_fee')) && $calculatedFee < $minFee) {
            $calculatedFee = $minFee;
        }

        if (! empty($maxFee = ArrayHelper::get($attributes, 'max_fee')) && $calculatedFee > $maxFee) {
            $calculatedFee = $maxFee;
        }

        return $calculatedFee;
    }

    /**
     * See if free shipping is available based on the package and cart.
     *
     * @since 2.14.0
     *
     * @param array $package Shipping package.
     * @return bool
     */
    public function is_available($package)
    {
        $isAvailable = false;
        $ignoreDiscounts = apply_filters($this->id.'_ignore_discounts', 'yes');
        $total = WC()->cart->get_displayed_subtotal();

        if (WC()->cart->display_prices_including_tax()) {
            $total = $total - WC()->cart->get_discount_tax();
        }

        if ('no' === $ignoreDiscounts) {
            $total = $total - WC()->cart->get_discount_total();
        }

        if ($this->formatPrice($total) >= $this->min_amount) {
            // Meet minimum amount condition.
            $isAvailable = true;
        }

        return apply_filters("woocommerce_shipping_{$this->id}_is_available", $isAvailable, $package, $this);
    }

    /**
     * Format price.
     *
     * @since 2.14.0
     * @param string|int|float $price
     * @return float $price
     */
    public function formatPrice($price)
    {
        if (! is_numeric($price)) {
            $price = floatval($price);
        }

        return round($price, wc_get_price_decimals(), PHP_ROUND_HALF_UP);
    }
}
