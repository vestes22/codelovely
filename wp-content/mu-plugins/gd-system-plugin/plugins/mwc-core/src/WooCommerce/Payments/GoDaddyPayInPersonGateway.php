<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PaymentsProviderSettingsException;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\Notices;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\PaymentMethodsListTable;
use GoDaddy\WordPress\MWC\Dashboard\Exceptions\OrderNotFoundException;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use WC_Data_Store;
use WC_Order;
use WC_Shipping_Method;
use WC_Shipping_Zone;

/**
 * GoDaddy Pay In Person payment Gateway.
 */
class GoDaddyPayInPersonGateway extends AbstractPaymentGateway
{
    /** @var string method description. */
    public $method_description = 'Customers can buy online and pay in person with orders synced to your Smart Terminal.';

    /** @var string method title. */
    public $method_title = 'GoDaddy Payments - Selling in Person';

    /** @var array Shipping methods that payment enabled for. If empty - accepted all the shipping methods */
    protected $enableForMethods;

    /** @var string provider name. */
    protected $providerName = 'godaddy-payments-payinperson';

    /** @var bool shipping methods validation status. */
    protected $isShippingMethodInvalid;

    /** @var string[] default shipping methods for GoDaddy Payments - Selling in Person */
    protected $defaultEnableForMethods = ['local_pickup', 'mwc_local_delivery', 'local_pickup_plus'];

    /** @var string WooCommerce processing order status. */
    const PROCESSING_STATUS = 'processing';

    /**
     * Constructs the GoDaddy Payment Gateway.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->id = $this->providerName;
        $this->enableForMethods = ArrayHelper::wrap($this->get_option('enable_for_methods', $this->defaultEnableForMethods));

        $this->init_form_fields();
        $this->init_settings();
        $this->isShippingMethodInvalid = false;

        $this->updateConfigurationFromSettings([
            'enabled' => 'enabled',
        ]);

        $this->maybeDisablePaymentGateway();

        $this->registerInformationHooks();

        parent::__construct();

        Register::action()
            ->setGroup('woocommerce_update_options_payment_gateways_'.$this->id)
            ->setHandler([$this, 'process_admin_options'])
            ->execute();

        $this->enqueueStyles();
    }

    /**
     * Render the styles for the payment method.
     *
     * @throws Exception
     */
    protected function enqueueStyles()
    {
        Enqueue::style()
            ->setHandle("{$this->id}-main-styles")
            ->setSource(WordPressRepository::getAssetsUrl('css/pay-in-person-method.css'))
            ->execute();
    }

    /**
     * Process the payment and return the result.
     *
     * This method is mainly used to change the default order status from 'On-Hold' to 'Processing'
     *
     * Note: this method is called by WooCommerce, so it needs to remain snake_case.
     *
     * @param $order_id
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return array
     */
    public function process_payment($order_id, AbstractPaymentMethod $paymentMethod = null) : array
    {
        try {
            $wooOrder = OrdersRepository::get($order_id);

            if (! $wooOrder) {
                throw new OrderNotFoundException('Order not found or WooCommerce is inactive');
            }

            $defaultOrderStatus = apply_filters('mwc_payments_'.$this->providerName.'_process_payment_order_status', static::PROCESSING_STATUS, $wooOrder);
            $wooOrder->update_status($defaultOrderStatus, __('Payment to be made upon delivery.', 'mwc-core'));

            // Remove cart.
            $woocommerce = WooCommerceRepository::getInstance();
            if (isset($woocommerce->cart)) {
                $woocommerce->cart->empty_cart();
            }

            return (array) apply_filters('mwc_payments_'.$this->providerName.'_after_process_payment', [
                'result'   => 'success',
                'redirect' => $this->get_return_url($wooOrder),
            ], $wooOrder);
        } catch (Exception $exception) {
            return [
                'result'  => 'failure',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Determines if the gateway should be active for use.
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive() : bool
    {
        // bail early if BOPIT feature is purposefully disabled
        if (! Notices::isBOPITFeatureEnabled()) {
            return false;
        }

        // bail early if GoDaddy Payments status is declined or terminated or if Poynt was removed
        if (Onboarding::getStatus() === Onboarding::STATUS_DECLINED || 'no' === get_option('mwc_payments_poynt_active')) {
            return false;
        }

        // bail if not on the MWC non-reseller plan
        if (ManagedWooCommerceRepository::isReseller()) {
            return false;
        }

        // consider it available if onboarding had been previously started
        if (Poynt::getServiceId()) {
            return true;
        }

        $woocommerce = WooCommerceRepository::getInstance();

        // otherwise adhere to the requirements for new users
        // TODO: we should look to move Woo methods like these under a WooCommerceConfiguration repository of sorts {@cwiseman 2021-05-28}
        return
            $woocommerce
            && $woocommerce->countries
            && 'US' === $woocommerce->countries->get_base_country()
            && 'USD' === get_woocommerce_currency();
    }

    /**
     * Renders the admin options.
     *
     * @throws Exception
     */
    public function admin_options()
    {
        ?>
        <h2 class="mwc-payments-godaddy-settings-title">
            <?php echo esc_html($this->get_method_title()); ?>
            <?php wc_back_link(__('Return to payments', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout')); ?>
            <?php echo PaymentMethodsListTable::getStatusHtml(Onboarding::getStatus(), $this); ?>
        </h2>
        <?php if (Poynt::hasPoyntSmartTerminalActivated()) : ?>
            <p class="mwc-payments-godaddy-settings-description">
                <?php echo esc_html__($this->method_description, 'mwc-core'); ?>
            </p>
            <p class="mwc-payments-godaddy-settings-description">
                <?php printf(
                    esc_html__('%1$sShop Smart Terminal%2$s', 'mwc-core'),
                    '<a href="'.esc_url($this->getSmartTerminalProductPageUrl()).'" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ); ?>
                &nbsp;|&nbsp;
                <?php
                $poyntBusinessId = Poynt::getBusinessId();
        $poyntHubUrl = StringHelper::trailingSlash(Poynt::getHubUrl());
        printf(
                    esc_html__('%1$sDevices%2$s', 'mwc-core'),
                    '<a href="'.esc_url(add_query_arg('businessId', $poyntBusinessId, $poyntHubUrl.'in-person/devices')).'" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ); ?>
                &nbsp;|&nbsp;
                <?php printf(
                    esc_html__('%1$sCatalogs%2$s', 'mwc-core'),
                    '<a href="'.esc_url(add_query_arg('businessId', $poyntBusinessId, $poyntHubUrl.'in-person/catalog')).'" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ); ?>
                &nbsp;|&nbsp;
                <?php printf(
                    esc_html__('%1$sCustomize Terminal%2$s', 'mwc-core'),
                    '<a href="'.esc_url(add_query_arg('businessId', $poyntBusinessId, $poyntHubUrl.'in-person/customization')).'" target="_blank">',
                    ' <span class="dashicons dashicons-external"></span></a>'
                ); ?>
            </p>
        <?php endif; ?>
        <?php if (! Poynt::hasPoyntSmartTerminalActivated()) {
                    $GLOBALS['hide_save_button'] = true;
                    echo '<p>'.__($this->method_description, 'mwc-core').'
            <div class="mwc-payments-godaddy-settings-no-order">
                <div class="mwc-payments-godaddy-settings-no-order-upper">
                    <h4>'.__('Smart Terminal', 'mwc-core').'</h4>
                    <h2>'.__('Dual screens for smoother selling.', 'mwc-core').'</h2>
                    <p>'.__('Our dual screens make check out a breeze. Plus, our all-in-one terminal includes a built-in payment processor, scanner, printer, security and more.', 'mwc-core').'</p>
                </div>
                <div class="mwc-payments-godaddy-settings-no-order-lower">
                    <div class="mwc-payments-godaddy-settings-no-order-lower-inner">
                        <div class="mwc-payments-godaddy-settings-no-order-price">
                            <span class="mwc-payments-godaddy-settings-no-order-price-sale">$249</span><span class="mwc-payments-godaddy-settings-no-order-price-linethrough">$499</span>
                        </div>
                        <div class="mwc-payments-godaddy-settings-no-order-badges">
                            <span class="mwc-payments-godaddy-settings-no-order-free">'.__('Free', 'mwc-core').'</span><span class="mwc-payments-godaddy-settings-no-order-shipping">'.__('2-day shipping.', 'mwc-core').'</span>
                        </div>
                        <div class="mwc-payments-godaddy-settings-no-order-btn">
                            <a target="_blank" href="'.esc_url($this->getSmartTerminalProductPageUrl()).'">'.__('Learn More', 'mwc-core').'</a>
                        </div>
                    </div>
                </div>
            </div>';
                } else {
                    echo '<div class="mwc-payments-godaddy-sip" id="mwc-payments-godaddy-sip-settings">
                <div class="mwc-payments-godaddy-sip__title">'.__('Settings', 'mwc-core').'</div>
                <table class="form-table">'.$this->generate_settings_html($this->get_form_fields(), false).'</table>
            </div> <!-- end of mwc-payments-godaddy-sip -->';
                }

        $this->display_errors();
    }

    /**
     * Determines if the gateway is available at checkout.
     *
     * Checks Woo's parent status, then that the site is connected and has credentials.
     *
     * Note: this method is called by WooCommerce, so it needs to remain snake_case.
     *
     * @return bool
     */
    public function is_available()
    {
        try {
            if (! $this->isParentAvailable() || ! $this->isChosenShippingMethodAccepted($this->enableForMethods) || ! $this->orderNeedsShipping()) {
                return false;
            }

            if (! $woocommerce = WooCommerceRepository::getInstance()) {
                return false;
            }

            if ($woocommerce->customer && 'US' !== $woocommerce->customer->get_shipping_country()) {
                return false;
            }
        } catch (Exception $exception) {
            // TODO: log the error {@cwiseman 2021-05-21}
            return false;
        }

        return true;
    }

    /**
     * Determines whether the cart / order needs shipping.
     *
     * @return bool
     * @throws Exception
     */
    public function orderNeedsShipping()
    {
        $woocommerce = WooCommerceRepository::getInstance();

        if ($woocommerce && $woocommerce->cart && $woocommerce->cart->needs_shipping()) {
            return apply_filters('woocommerce_cart_needs_shipping', true);
        }

        // @TODO: Replace this with internal functionality and Order Model {JO: 2021-10-08}
        if (0 < get_query_var('order-pay') && is_page(wc_get_page_id('checkout'))) {
            $orderId = absint(get_query_var('order-pay'));

            if ($order = OrdersRepository::get($orderId)) {
                return apply_filters('woocommerce_cart_needs_shipping', ! $this->orderIsVirtual($order));
            }
        }

        return apply_filters('woocommerce_cart_needs_shipping', false);
    }

    /**
     * Determines if the parent is_available() method is available.
     *
     * @return bool
     * @throws Exception
     */
    protected function isParentAvailable()
    {
        return parent::is_available();
    }

    /**
     * Registers the information hooks.
     *
     * @return void
     * @throws Exception
     */
    protected function registerInformationHooks()
    {
        Register::action()
            ->setGroup('woocommerce_email_before_order_table')
            ->setHandler([$this, 'instructionsEmail'])
            ->setPriority(20)
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Sets the order received text for the customer.
     *
     * @param string $text
     * @param WC_Order $order
     * @return string
     */
    public function maybeSetOrderReceivedText($text, $order)
    {
        // Only show if the user checkout using this payment method
        if ($this->providerName === $order->get_payment_method()) {
            $text = $this->get_option('instructions', $this->getDefaultInstructions());
        }

        return wp_kses_post(wpautop(wptexturize($text)));
    }

    /**
     * Adds instructions to WC order emails sent to the customers.
     *
     * @param WC_Order $order Order object.
     * @param bool $sentToAdmin Sent to admin.
     */
    public function instructionsEmail($order, $sentToAdmin)
    {
        if (! $sentToAdmin && $this->providerName === $order->get_payment_method()) {
            echo wp_kses_post(wpautop(wptexturize($this->get_option('instructions', $this->getDefaultInstructions()))).PHP_EOL);
        }
    }

    /**
     * Check is chosen on checkout shipping method accepted by current payment gateway.
     *
     * @param array $enabledForMethods
     *
     * @return bool
     * @throws Exception
     */
    private function isChosenShippingMethodAccepted(array $enabledForMethods = []) : bool
    {
        $woocommerce = WooCommerceRepository::getInstance();

        if (! empty($enabledForMethods && $woocommerce && $woocommerce->session)) {
            $chosenShippingMethod = current($woocommerce->session->get('chosen_shipping_methods'));

            if (! empty($chosenShippingMethod)) {
                $chosenShippingMethodNameElems = explode(':', $chosenShippingMethod);

                $available_methods = ArrayHelper::where($enabledForMethods, function ($method) use ($chosenShippingMethodNameElems) {
                    $methodElems = explode(':', $method);

                    return count($methodElems) == 1
                        // name consist of only 1 element - then it support any shipping method with current shipping name(id)
                        ? is_array($chosenShippingMethodNameElems) && ArrayHelper::contains(explode(':', $method), $chosenShippingMethodNameElems[0])
                        : implode(':', $chosenShippingMethodNameElems) === $method;
                });

                return ! empty($available_methods);
            }
        }

        return true;
    }

    /**
     * Can the order be refunded via this gateway?
     *
     * @param WC_Order $wcOrder Order object.
     * @return bool If false, the automatic refund button is hidden in the UI.
     * @throws BaseException
     */
    public function can_refund_order($wcOrder)
    {
        $order = $this->getOrderAdapter($wcOrder)->convertFromSource();

        $orderTransaction = $this->getOrderTransactionDataStore('poynt')->read($order->getId(), 'payment');

        if (! $orderTransaction->getRemoteId()) {
            return false;
        }

        return parent::can_refund_order($wcOrder);
    }

    /**
     * Gets the adapter for the given WooCommerce order.
     *
     * @param WC_Order $wcOrder
     * @return OrderAdapter
     */
    protected function getOrderAdapter($wcOrder) : OrderAdapter
    {
        return new OrderAdapter($wcOrder);
    }

    /**
     * Gets instance of data store for given provider's transaction.
     *
     * @param string $providerName
     * @return OrderTransactionDataStore
     */
    protected function getOrderTransactionDataStore(string $providerName) : OrderTransactionDataStore
    {
        return new OrderTransactionDataStore($providerName);
    }

    /**
     * Initializes the WooCommerce settings.
     */
    public function init_settings()
    {
        $this->initParentSettings();

        $this->has_fields = true;

        $this->title = $this->settings['title'] ?? $this->getDefaultTitle();
        $this->description = $this->settings['description'] ?? $this->getDefaultDescription();
    }

    /**
     * Initializes settings from the parent class.
     */
    protected function initParentSettings()
    {
        parent::init_settings();
    }

    /**
     * Renders the payment fields.
     */
    public function payment_fields()
    {
        ?>
        <p><?php echo wp_kses_post($this->get_description()); ?></p>
        <?php
    }

    /**
     * Gets the payment method icon, for display at checkout.
     *
     * @return string
     */
    public function get_icon()
    {
        try {
            $imageUrl = WordPressRepository::getAssetsUrl('images/payments/selling-in-person/checkout-icon.svg');
        } catch (Exception $exception) {
            return '';
        }

        if (empty($imageUrl)) {
            return '';
        }

        ob_start(); ?>
        <div class="mwc-payments-gateway-card-icons">
            <img src="<?php echo esc_url($imageUrl); ?>" alt="Pay in person icon" width="40" height="25" style="width: 40px; height: 25px;"/>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets a card payment method to add.
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    protected function getPaymentMethodForAdd() : AbstractPaymentMethod
    {
        $nonce = StringHelper::sanitize((string) filter_input(
            INPUT_POST,
            "mwc-payments-{$this->providerName}-payment-nonce",
            FILTER_SANITIZE_STRING
        ));

        $paymentMethod = $this->getCardPaymentMethod()->setRemoteId($nonce);

        if ($currentUser = User::getCurrent()) {
            $paymentMethod->setCustomerId($currentUser->getId());
        }

        return $paymentMethod;
    }

    /**
     * Gets a new instance of card payment method.
     *
     * @return CardPaymentMethod
     */
    protected function getCardPaymentMethod() : CardPaymentMethod
    {
        return new CardPaymentMethod();
    }

    /**
     * Initialise settings form fields.
     */
    public function init_order_guide()
    {
    }

    /**
     * Initialise settings form fields.
     *
     * @throws Exception
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled'            => [
                'title'    => esc_html__('Enable Selling in Person', 'mwc-core'),
                'type'     => 'checkbox',
                'label'    => '<span></span>',
                'default'  => 'no',
                'disabled' => ! static::canEnablePaymentGateway(),
            ],
            'title'              => [
                'title'    => esc_html__('Checkout title', 'mwc-core'),
                'type'     => 'text',
                'desc_tip' => esc_html__('Payment method title that the customer will see during checkout.', 'mwc-core'),
                'default'  => $this->getDefaultTitle(),
            ],
            'description'        => [
                'title'    => esc_html__('Checkout description', 'mwc-core'),
                'type'     => 'textarea',
                'desc_tip' => esc_html__('Payment method description that the customer will see during checkout.', 'mwc-core'),
                'default'  => $this->getDefaultDescription(),
            ],
            'instructions'       => [
                'title'    => esc_html__('Order received instructions', 'mwc-core'),
                'type'     => 'textarea',
                'default'  => $this->getDefaultInstructions(),
                'desc_tip' => esc_html__('Message that the customer will see on the order received page and in the processing order email after checkout.', 'mwc-core'),
            ],
            'enable_for_methods' => [
                'title'             => __('Enable for Shipping Methods', 'mwc-core'),
                'type'              => 'multiselect',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 400px;',
                'default'           => $this->defaultEnableForMethods,
                'options'           => $this->loadShippingMethodOptions(),
                'desc_tip'          => esc_html__('Select the shipping methods that will show this payment method for the customer during checkout.', 'mwc-core'),
                'custom_attributes' => [
                    'data-placeholder' => __('Select Shipping Methods', 'mwc-core'),
                ],
            ],
        ];
    }

    /**
     * Validates MultiSelect Field to determine whether the user has selected any Shipping Method.
     *
     * Note: this method is called by WooCommerce, so it needs to remain snake_case.
     *
     * @return array|null
     */
    public function validate_multiselect_field($key, $value)
    {
        if ('enable_for_methods' === $key && empty($value)) {
            $this->isShippingMethodInvalid = true;
            $this->add_error(__('At least one shipping method is required to enable Selling in Person.', 'mwc-core'));
        }

        return $value;
    }

    /**
     * Gets the default gateway description.
     *
     * This is used to fill the Description setting for display at checkout.
     *
     * @return string
     */
    private function getDefaultDescription() : string
    {
        return esc_html__('Pay for your order in-person at pickup or delivery.', 'mwc-core');
    }

    /**
     * Gets the default instructions.
     *
     * This is used to get default instructions for the Thank you order page and email.
     *
     * @return string
     */
    private function getDefaultInstructions() : string
    {
        return esc_html__('We accept major credit/debit cards and cash.', 'mwc-core');
    }

    /**
     * Gets the default gateway title.
     *
     * This is used to fill the Title setting for display at checkout.
     *
     * @return string
     */
    private function getDefaultTitle() : string
    {
        return esc_html__('Pay in Person', 'mwc-core');
    }

    /**
     * Loads all the shipping method options for the enable_for_methods field.
     *
     * @param array $regions for which shipping methods will be loaded
     *
     * @return array
     * @throws Exception
     */
    protected function loadShippingMethodOptions(array $regions = ['US']) : array
    {
        $options = [];
        $woocommerce = WooCommerceRepository::getInstance();

        // Since this is expensive, we only want to do it if we're actually on the payment settings page.
        if (! $this->isAccessingSettings() || ! $woocommerce) {
            return [];
        }

        foreach ($woocommerce->shipping()->load_shipping_methods() as $method) {
            $options[$method->get_method_title()][$method->id] = $this->getShippingMethodOptionsText($method);

            foreach ($this->getAvailableShippingZones($regions) as $zone) {
                foreach ($zone->get_shipping_methods() as $shipping_method_instance_id => $shipping_method_instance) {
                    if ($shipping_method_instance->id !== $method->id) {
                        continue;
                    }

                    // Translators: %1$s shipping method title, %2$s shipping method id.
                    $option_instance_title = sprintf(esc_html__('%1$s (#%2$s)', 'mwc-core'), $shipping_method_instance->get_title(), $shipping_method_instance_id);

                    // Translators: %1$s zone name, %2$s shipping method instance name.
                    $option_title = sprintf(esc_html__('%1$s &ndash; %2$s', 'mwc-core'), $zone->get_id() ? $zone->get_zone_name() : esc_html__('Other locations', 'mwc-core'), $option_instance_title);

                    $options[$method->get_method_title()][$shipping_method_instance->get_rate_id()] = $option_title;
                }
            }
        }

        return $options;
    }

    /**
     * Return the methods shipping options text.
     *
     * @param array $availableRegions
     * @return array
     * @throws Exception
     */
    protected function getAvailableShippingZones(array $availableRegions = []) : array
    {
        if (! WooCommerceRepository::isWooCommerceActive()) {
            return [];
        }

        $zones = [];
        $rawZones = $this->getWooCommerceRawShippingZones();
        $rawZones[] = (object) ['zone_id' => 0];

        // add only zones with accepted regions
        foreach ($rawZones as $rawZone) {
            $zone = $this->getWooCommerceShippingZoneInstance($rawZone);

            $locations_filtered = ArrayHelper::where(ArrayHelper::wrap($zone->get_zone_locations()), function ($location) use ($availableRegions) {
                return empty($availableRegions) || ArrayHelper::contains($availableRegions, current(explode(':', $location->code)));
            });

            if (! empty($locations_filtered)) {
                $zones[] = $zone;
            }
        }

        return $zones;
    }

    /**
     * Gets WooCommerce raw shipping zones data list.
     *
     * @return array
     * @throws Exception
     */
    protected function getWooCommerceRawShippingZones() : array
    {
        $data_store = WC_Data_Store::load('shipping-zone');

        return $data_store ? (array) $data_store->get_zones() : [];
    }

    /**
     * Gets an instance of WooCommerce shipping zone object for the given raw zone data.
     *
     * @param mixed $rawZoneData
     * @return WC_Shipping_Zone
     */
    protected function getWooCommerceShippingZoneInstance($rawZoneData) : WC_Shipping_Zone
    {
        return new WC_Shipping_Zone($rawZoneData);
    }

    /**
     * Gets the method shipping options text.
     *
     * @param WC_Shipping_Method $method
     * @return string
     */
    protected function getShippingMethodOptionsText(WC_Shipping_Method $method) : string
    {
        return 'local_pickup_plus' === $method->id
            ? __('Local Pickup Plus method', 'mwc-core')
            /* translators: Placeholders: %1$s - Shipping Method name */
            : sprintf(__('Any "%1$s" method', 'mwc-core'), $method->get_method_title());
    }

    /**
     * Checks to see whether the appropriate payment settings are being accessed by the current request.
     *
     * @return bool
     */
    protected function isAccessingSettings() : bool
    {
        if (is_admin()) {
            // phpcs:disable WordPress.Security.NonceVerification
            if (! isset($_REQUEST['page']) || 'wc-settings' !== $_REQUEST['page']) {
                return false;
            }

            // @TODO: Add helper for these to WordPressRepository -- isCurrentTab {JO: 2021-09-16}
            if (! isset($_REQUEST['tab']) || 'checkout' !== $_REQUEST['tab']) {
                return false;
            }

            // @TODO: Add helper for these to WordPressRepository -- isCurrentSection {JO: 2021-09-16}
            if (! isset($_REQUEST['section']) || $this->providerName !== $_REQUEST['section']) {
                return false;
            }

            // phpcs:enable WordPress.Security.NonceVerification

            return true;
        }

        if ($this->isRestRequest() && StringHelper::contains($this->getCurrentQueryRestRoute(), '/payment_gateways')) {
            return true;
        }

        return false;
    }

    /**
     * Gets current WP Query REST API route.
     *
     * @return string
     */
    protected function getCurrentQueryRestRoute() : string
    {
        // @TODO: Add helper for this to WordPressRepository as we shouldn't be using globals directly {JO: 2021-09-16}
        global $wp;

        return $wp->query_vars['rest_route'] ?? '';
    }

    /**
     * Determines if the request is a REST API request.
     *
     * @return bool
     */
    protected function isRestRequest() : bool
    {
        return defined('REST_REQUEST') && REST_REQUEST;
    }

    /**
     * Update configuration values based on WooCommerce settings.
     *
     * @param null $configurations
     * @return void
     * @throws PaymentsProviderSettingsException
     */
    protected function updateConfigurationFromSettings($configurations = null)
    {
        $configurations = $configurations ?: array_keys($this->form_fields);

        parent::updateConfigurationFromSettings($configurations);
    }

    /**
     * Determines if Pay in Person can be enabled.
     *
     * @return bool
     * @throws Exception
     */
    public static function canEnablePaymentGateway() : bool
    {
        $isGdpSuspended = Onboarding::STATUS_SUSPENDED === Onboarding::getStatus();
        $isGdpTerminatedOrDisconnected = in_array(Onboarding::getStatus(), [Onboarding::STATUS_TERMINATED, Onboarding::STATUS_DISCONNECTED], true);
        $isPaymentsDisabled = ! Onboarding::paymentsEnabled();

        return Poynt::hasPoyntSmartTerminalActivated()
            && ! $isGdpTerminatedOrDisconnected
            && ! ($isGdpSuspended && $isPaymentsDisabled);
    }

    /**
     * May disable the payment gateway if it can't be enabled.
     *
     * @throws Exception
     */
    protected function maybeDisablePaymentGateway()
    {
        if (! static::canEnablePaymentGateway()) {
            $settings = get_option('woocommerce_godaddy-payments-payinperson_settings', []);

            if (isset($settings['enabled'])) {
                $settings['enabled'] = 'no';

                update_option('woocommerce_godaddy-payments-payinperson_settings', $settings);
            }
        }
    }

    /**
     * Gets the Smart Terminal product page URL.
     *
     * @return string
     * @throws Exception
     */
    protected function getSmartTerminalProductPageUrl() : string
    {
        return sprintf(
            'https://payments.godaddy.com/in-person/shop/78d12aa5-6b75-4669-b661-8501023046c9%s',
            ! empty($id = Poynt::getBusinessId()) ? '?businessId='.$id : ''
        );
    }
}
