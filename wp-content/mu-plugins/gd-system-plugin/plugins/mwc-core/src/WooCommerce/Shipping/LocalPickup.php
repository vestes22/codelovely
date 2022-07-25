<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\Features\IsConditionalFeatureTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Emails\ReadyForPickupEmail;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\LocalPickup\Emails;
use WC_Email_Customer_Processing_Order;
use WC_Order;
use WC_Shipping_Rate;

/**
 * Integration class for the Local Pickup shipping method.
 */
class LocalPickup
{
    use IsConditionalFeatureTrait;

    /**
     * Emails that should show pickup instructions.
     *
     * @var array
     */
    protected $emailsToIncludePickupInstructions = [
        ReadyForPickupEmail::class,
        WC_Email_Customer_Processing_Order::class,
    ];

    /**
     * Determines whether this feature should be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        // should not display if Bopit functionality is disabled through configurations
        if (! self::isActive()) {
            return false;
        }

        return WooCommerceRepository::isWooCommerceActive() && wc_shipping_enabled();
    }

    /**
     * Checks if Bopit feature flag is set.
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive() : bool
    {
        return Configuration::get('features.bopit', true);
    }

    /**
     * Local pickup constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Adds the hooks.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        new Emails();

        Register::filter()
            ->setGroup('woocommerce_shipping_instance_form_fields_local_pickup')
            ->setHandler([$this, 'addLocalPickupInstructionFields'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_after_shipping_rate')
            ->setHandler([$this, 'maybeAddCheckoutDescription'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_email_customer_details')
            ->setPriority(30) // Core hooks into 10 and 20. Making an assumption that we want to be at the bottom.
            ->setArgumentsCount(4)
            ->setHandler([$this, 'maybeAddPickupInstructionsToEmails'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_thankyou')
            ->setPriority(1)
            ->setHandler([$this, 'maybeAddPickupInstructionsToThankYouPage'])
            ->execute();
    }

    /**
     * Adds local pickup instruction fields to shipping rate instances.
     *
     * @internal
     *
     * @param array|null $instanceFields
     * @return array
     */
    public function addLocalPickupInstructionFields(array $instanceFields = null) : array
    {
        $instanceFields = ArrayHelper::wrap($instanceFields);

        $instanceFields['checkout_description'] = [
            'title'       => __('Checkout description', 'mwc-core'),
            'type'        => 'textarea',
            'description' => __('Shipping method description that the customer will see during checkout.', 'mwc-core'),
            'default'     => '',
            'desc_tip'    => true,
        ];

        $instanceFields['pickup_instructions'] = [
            'title'       => __('Pickup instructions', 'mwc-core'),
            'type'        => 'textarea',
            'description' => __('Message that the customer will see on the order received page as well as in the processing order and ready for pickup emails.', 'mwc-core'),
            'default'     => '',
            'desc_tip'    => true,
        ];

        return $instanceFields;
    }

    /**
     * Retrieves shipping method instance settings.
     *
     * @TODO: object is a valid type hint as of PHP 7.2 -- update this to require $method to be an object when 7.2 is the minimum {JO: 2021-09-09}
     *
     * @param mixed $method object The active shipping method
     * @return array
     */
    protected function getShippingMethodInstanceSettings($method)
    {
        return ArrayHelper::wrap(get_option(sprintf('woocommerce_%s_%d_settings', $method->get_method_id(), $method->get_instance_id())));
    }

    /**
     * Conditionally adds checkout description to checkout and cart.
     *
     * @internal
     *
     * @param WC_Shipping_Rate $method Shipping rate object
     */
    public function maybeAddCheckoutDescription($method)
    {
        if ('local_pickup' !== $method->get_method_id()) {
            return;
        }

        $checkoutDescription = ArrayHelper::get($this->getShippingMethodInstanceSettings($method), 'checkout_description');

        if (! empty($checkoutDescription)) {
            echo '<p>'.wp_kses_post($checkoutDescription).'</p>';
        }
    }

    /**
     * Retrieves pickup instructions from shipping method instance on WC_Order object.
     *
     * @param WC_Order $order the WooCommerce Order object
     * @return string
     */
    protected function getPickupInstructionsFromOrder($order) : string
    {
        $shippingMethods = $order->get_shipping_methods();

        if (! empty($shippingMethods)) {
            // @TODO: What happens here if the shipping method child is empty or not an object?  Not sure I trust Woo to always deliver {JO: 2021-09-09}
            $primaryShippingMethod = array_pop($shippingMethods);

            return ArrayHelper::get($this->getShippingMethodInstanceSettings($primaryShippingMethod), 'pickup_instructions') ?: '';
        }

        return '';
    }

    /**
     * Conditionally adds pickup instructions to order received page, processing order email, and ready for pickup email.
     *
     * @internal
     *
     * @param WC_Order $order the WooCommerce Order object
     * @param bool $sentToAdmin
     * @param bool $plainText
     * @param object $email
     */
    public function maybeAddPickupInstructionsToEmails($order, $sentToAdmin, $plainText, $email)
    {
        if (! ArrayHelper::contains($this->emailsToIncludePickupInstructions, get_class($email))) {
            return;
        }

        $pickupInstructions = $this->getPickupInstructionsFromOrder($order);

        if (! empty($pickupInstructions)) {
            $this->renderPickupInstructions($pickupInstructions, $plainText);
        }
    }

    /**
     * Renders the pickup instructions to order received page, processing order email, and ready for pickup email.
     *
     * @param string $pickupInstructions The pickup instructions text
     * @param bool $plainText Should the information be rendered in plain text
     */
    protected function renderPickupInstructions(string $pickupInstructions, bool $plainText = false)
    {
        if ($plainText) {
            echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
            _e('Pickup Instructions', 'mwc-core');
            echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
            echo "\n\n----------------------------------------\n\n";
            echo esc_html(wp_strip_all_tags(wptexturize($pickupInstructions)));
            echo "\n\n----------------------------------------\n\n";

            return;
        }

        echo '<h2>'.__('Pickup Instructions', 'mwc-core').'</h2>';
        echo '<p>'.wp_kses_post($pickupInstructions).'</p>';
    }

    /**
     * Adds pickup instructions to thank you page.
     *
     * @internal
     *
     * @param int Order ID for WC_Order
     * @throws Exception
     */
    public function maybeAddPickupInstructionsToThankYouPage($orderId)
    {
        $wcOrder = OrdersRepository::get((int) $orderId);

        if (! $wcOrder instanceof \WC_Order) {
            return;
        }

        $pickupInstructions = $this->getPickupInstructionsFromOrder($wcOrder);

        if (! empty($pickupInstructions)) {
            // @TODO: Really should just use the render above, but didn't want to mess with the class injection at the moment -- update later {JO: 2021-09-09}
            echo '<h2 class="woocommerce-column__title">'.__('Pickup Instructions', 'mwc-core').'</h2>';
            echo '<p>'.wp_kses_post($pickupInstructions).'</p>';
        }
    }
}
