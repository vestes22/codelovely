<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PaymentsProviderSettingsException;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;
use WC_Order;
use WC_Payment_Gateway;

/**
 * GoDaddy Payments Apple Pay Gateway.
 */
class ApplePayGateway extends WC_Payment_Gateway
{
    /** @var int default button height */
    const BUTTON_HEIGHT_DEFAULT = 45;

    /** @var int max button height */
    const BUTTON_HEIGHT_MAX = 64;

    /** @var int min button height */
    const BUTTON_HEIGHT_MIN = 30;

    /** @var string flag to display button on cart page */
    const BUTTON_PAGE_CART = 'CART';

    /** @var string flag to display button on checkout page */
    const BUTTON_PAGE_CHECKOUT = 'CHECKOUT';

    /** @var string flag to display button on single product pages */
    const BUTTON_PAGE_SINGLE_PRODUCT = 'SINGLE_PRODUCT';

    /** @var string black button style */
    const BUTTON_STYLE_BLACK = 'BLACK';

    /** @var string white button style */
    const BUTTON_STYLE_WHITE = 'WHITE';

    /** @var string button style with white outline */
    const BUTTON_STYLE_WHITE_WITH_LINE = 'WHITE_WITH_LINE';

    /** @var string button type with no text, only logo */
    const BUTTON_TYPE_PLAIN = 'PLAIN';

    /** @var string "Book with Apple Pay" button type */
    const BUTTON_TYPE_BOOK = 'BOOK';

    /** @var string "Buy with Apple Pay" button type */
    const BUTTON_TYPE_BUY = 'BUY';

    /** @var string "Donate with Apple Pay" button type */
    const BUTTON_TYPE_DONATE = 'DONATE';

    /** @var string "Check out with Apple Pay" button type */
    const BUTTON_TYPE_CHECKOUT = 'CHECKOUT';

    /** @var string "Continue with Apple Pay" button type */
    const BUTTON_TYPE_CONTINUE = 'CONTINUE';

    /** @var string "Contribute with Apple Pay" button type */
    const BUTTON_TYPE_CONTRIBUTE = 'CONTRIBUTE';

    /** @var string "Order with Apple Pay" button type */
    const BUTTON_TYPE_ORDER = 'ORDER';

    /** @var string "Pay with Apple Pay" button type */
    const BUTTON_TYPE_PAY = 'PAY';

    /** @var string "Rent with Apple Pay" button type */
    const BUTTON_TYPE_RENT = 'RENT';

    /** @var string "Support with Apple Pay" button type */
    const BUTTON_TYPE_SUPPORT = 'SUPPORT';

    /** @var string "Tip with Apple Pay" button type */
    const BUTTON_TYPE_TIP = 'TIP';

    /**
     * Apple Pay gateway constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->id = 'godaddy-payments-apple-pay';
        $this->method_title = $this->title = 'GoDaddy Payments - Apple Pay';
        $this->method_description = __('Customers can buy online and pay with Apple Pay.', 'mwc-core');

        $this->init_form_fields();
        $this->updateConfigurationFromSettings();
        $this->addHooks();
    }

    /**
     * Adds hooks.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::action()
            ->setGroup('woocommerce_update_options_payment_gateways_'.$this->id)
            ->setHandler([$this, 'process_admin_options'])
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAdminScriptsAndStyles'])
            ->execute();

        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'enqueueFrontendScriptsAndStyles'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_order_get_payment_method_title')
            ->setHandler([$this, 'filterOrderPaymentMethodTitle'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Initializes the gateway settings form fields.
     *
     * @see WC_Payment_Gateway::init_settings()
     * @see WC_Payment_Gateway::get_form_fields()
     * @see WC_Payment_Gateway::generate_settings_html()
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'godaddy_payments_settings' => [
                'type' => 'parent_gateway_settings',
            ],
            'apple_pay_settings' => [
                'type'  => 'title',
                'title' => __('Apple Pay Settings', 'mwc-core'),
            ],
            'enabled' => [
                'title'    => __('Enable', 'mwc-core'),
                'label'    => __('Enable to add the payment method to your checkout.', 'mwc-core'),
                'type'     => 'checkbox',
                'default'  => 'no',
            ],
            'enabled_pages' => [
                'title'   => __('Pages to enable Apple Pay on', 'mwc-core'),
                'type'    => 'multiselect',
                'class'   => 'wc-enhanced-select',
                'default' => [
                    static::BUTTON_PAGE_CART,
                    static::BUTTON_PAGE_CHECKOUT,
                ],
                'options' => [
                    static::BUTTON_PAGE_CART           => __('Cart', 'mwc-core'),
                    static::BUTTON_PAGE_CHECKOUT       => __('Checkout', 'mwc-core'),
                    static::BUTTON_PAGE_SINGLE_PRODUCT => __('Single Product', 'mwc-core'),
                ],
            ],
            /* @link https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-types */
            'button_type' => [
                'title'       => __('Button label', 'mwc-core'),
                'description' => '<a href="https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-types" target="_blank">'.__('Check button labels here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'default'     => static::BUTTON_TYPE_BUY,
                'options'     => [
                    static::BUTTON_TYPE_PLAIN      => __('Logo only', 'mwc-core'),
                    static::BUTTON_TYPE_BOOK       => __('Book with', 'mwc-core'),
                    static::BUTTON_TYPE_BUY        => __('Buy with', 'mwc-core'),
                    static::BUTTON_TYPE_CHECKOUT   => __('Check Out with', 'mwc-core'),
                    static::BUTTON_TYPE_CONTRIBUTE => __('Contribute with', 'mwc-core'),
                    static::BUTTON_TYPE_DONATE     => __('Donate with', 'mwc-core'),
                    static::BUTTON_TYPE_ORDER      => __('Order with', 'mwc-core'),
                    static::BUTTON_TYPE_RENT       => __('Rent with', 'mwc-core'),
                    static::BUTTON_TYPE_SUPPORT    => __('Support with', 'mwc-core'),
                    static::BUTTON_TYPE_TIP        => __('Tip with', 'mwc-core'),
                ],
            ],
            /* @link https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-styles */
            'button_style' => [
                'title'       => __('Button style', 'mwc-core'),
                'description' => '<a href="https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-styles" target="_blank">'.__('Check button style here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'type'        => 'select',
                'class'       => 'wc-enhanced-select',
                'default'     => 'BLACK',
                'options'     => [
                    static::BUTTON_STYLE_BLACK           => __('Black', 'mwc-core'),
                    static::BUTTON_STYLE_WHITE           => __('White', 'mwc-core'),
                    static::BUTTON_STYLE_WHITE_WITH_LINE => __('White with outline', 'mwc-core'),
                ],
            ],
            /* @link https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-size-and-position */
            'button_height' => [
                'type'              => 'number',
                'title'             => __('Button height', 'mwc-core'),
                'description'       => __('Apple requests the button size match your cart/checkout button and be 30 to 64 pixels tall. The width is set automatically.', 'mwc-core').'<br><a href="https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/#button-size-and-position" target="_blank">'.__('Check button size here', 'mwc-core').' <span class="dashicons dashicons-external"></span></a>',
                'css'               => 'max-width: 105px',
                'default'           => static::BUTTON_HEIGHT_DEFAULT,
                'custom_attributes' => [
                    'step' => 1,
                    'min'  => static::BUTTON_HEIGHT_MIN,
                    'max'  => static::BUTTON_HEIGHT_MAX,
                ],
            ],
        ];
    }

    /**
     * Generates an HTML output for the parent gateway settings section.
     *
     * @see ApplePayGateway::init_form_fields()
     * @see ApplePayGateway::getParentGatewaySettingsSummaryHtml()
     *
     * @param string $key unused, passed by WooCommerce
     * @param array $data unused, passed by WooCommerce
     * @return string
     * @throws Exception
     */
    protected function generate_parent_gateway_settings_html(string $key = '', array $data = []) : string
    {
        /* @var GoDaddyPaymentsGateway $parentGateway */
        $parentGateway = CorePaymentGateways::getManagedPaymentGatewayInstance('poynt');

        if (! $parentGateway) {
            throw new Exception(__('Cannot load the GoDaddy Payments gateway.', 'mwc-core'));
        }

        $gdpGatewaySettingsUrl = SiteRepository::getAdminUrl('admin.php?page=wc-settings&tab=checkout&section=poynt');
        $sectionDescription = sprintf(
            /* translators: Placeholder: %s - the gateway settings page title, normally: "GoDaddy Payments - Credit/Debit card" */
            esc_html__('These settings are managed from the %s page', 'mwc-core'),
            '<a href="'.esc_url($gdpGatewaySettingsUrl).'">'.esc_html($parentGateway->get_method_title().' - '.$parentGateway->get_title()).'</a>'
        );

        ob_start(); ?>
        <div id="woocommerce-godaddy-payments-apple-pay-parent-gateway-settings-summary">
            <h3 class="wc-settings-sub-title" id="woocommerce_godaddy-payments-apple-pay-general-settings"><?php echo esc_html_x('General', 'General settings', 'mwc-core'); ?></h3>
            <p><?php echo $sectionDescription; ?></p>
            <div class="settings-table">
                <?php echo $this->getParentGatewaySettingsSummaryHtml($parentGateway); ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Generates an HTML output for the table rows summarizing the parent gateway settings.
     *
     * @see ApplePayGateway::admin_options()
     *
     * @param GoDaddyPaymentsGateway $parentGateway
     * @return string
     */
    protected function getParentGatewaySettingsSummaryHtml(GoDaddyPaymentsGateway $parentGateway) : string
    {
        ob_start();

        foreach ($parentGateway->get_form_fields() as $key => $fieldData) {
            if (! isset($fieldData['title']) || ! in_array($key, ['transaction_type', 'charge_virtual_orders', 'capture_paid_orders', 'enable_tokenization', 'enable_detailed_decline_messages', 'debug_mode'], true)) {
                continue;
            }

            $fieldLabel = $fieldData['title'];

            if ('transaction_type' === $key || 'debug_mode' === $key) {
                $valueLabel = $fieldData['options'][$parentGateway->get_option($key)];
            } else {
                $valueLabel = wc_string_to_bool($parentGateway->get_option($key)) ? __('Enabled', 'mwc-core') : __('Disabled', 'mwc-core');
            } ?>
            <div class="setting-row">
                <div class="setting-name"><?php echo ucfirst(strtolower(esc_html($fieldLabel))); ?>:</div>
                <div class="setting-value"><strong><?php echo esc_html($valueLabel); ?></strong></div>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * Processes and saves the gateway settings.
     *
     * @internal
     *
     * @throws PaymentsProviderSettingsException
     */
    public function process_admin_options() : bool
    {
        $updated = false;

        if (is_callable('parent::process_admin_options')) {
            $updated = (bool) parent::process_admin_options();
        }

        $this->updateConfigurationFromSettings();

        return $updated;
    }

    /**
     * Updates configuration values based on WooCommerce settings.
     *
     * @see GoDaddyPaymentsGateway::updateConfigurationFromSettings()
     *
     * @throws PaymentsProviderSettingsException
     */
    public function updateConfigurationFromSettings()
    {
        $settingKeys = [
            'enabled'       => 'enabled',
            'enabled_pages' => 'enabledPages',
            'button_type'   => 'buttonType',
            'button_height' => 'buttonHeight',
            'button_style'  => 'buttonStyle',
        ];

        foreach ($settingKeys as $wcSettingKey => $configKey) {
            $settingValue = $this->get_option($wcSettingKey);

            if (is_numeric($settingValue)) {
                // ensure type consistency of numerical settings like the button height
                $settingValue = (int) $settingValue;
            } elseif ('yes' === $settingValue || 'no' === $settingValue) {
                // normalizes WooCommerce boolean settings into true boolean values
                $settingValue = 'yes' === $settingValue;
            }

            try {
                Configuration::set("payments.applePay.{$configKey}", $settingValue);
            } catch (Exception $exception) {
                throw new PaymentsProviderSettingsException("Apple Pay configuration for {$configKey} could not be set.");
            }
        }
    }

    /**
     * Enqueues the gateway's admin scripts and styles.
     *
     * @internal callback
     * @see ApplePayGateway::addHooks()
     *
     * @throws Exception
     */
    public function enqueueAdminScriptsAndStyles()
    {
        $screen = WordPressRepository::getCurrentScreen();

        if (! $screen || "woocommerce_settings_checkout_{$this->id}" !== $screen->getPageId()) {
            return;
        }

        Enqueue::style()
            ->setHandle("{$this->id}-admin-settings")
            ->setSource(WordPressRepository::getAssetsUrl('css/apple-pay-settings.css'))
            ->execute();
    }

    /**
     * Enqueues the gateway's frontend scripts and styles.
     *
     * @internal callback
     * @see ApplePayGateway::addHooks()
     *
     * @throws Exception
     */
    public function enqueueFrontendScriptsAndStyles()
    {
        if (! static::isActive() || WordPressRepository::isAdmin()) {
            return;
        }

        $enabledPages = ArrayHelper::wrap(Configuration::get('payments.applePay.enabledPages', []));
        $shouldEnqueue = (ArrayHelper::contains($enabledPages, static::BUTTON_PAGE_CART) && WooCommerceRepository::isCartPage())
            || (ArrayHelper::contains($enabledPages, static::BUTTON_PAGE_CHECKOUT) && WooCommerceRepository::isCheckoutPage())
            || (ArrayHelper::contains($enabledPages, static::BUTTON_PAGE_SINGLE_PRODUCT) && WooCommerceRepository::isProductPage());

        if ($shouldEnqueue) {
            Enqueue::style()
                ->setHandle("{$this->id}-frontend")
                ->setSource(WordPressRepository::getAssetsUrl('css/apple-pay-frontend.css'))
                ->execute();
        }
    }

    /**
     * Processes a payment using the parent GoDaddy Payment gateway.
     *
     * Implements parent method.
     * @see GoDaddyPaymentsGateway::process_payment()
     *
     * @param int|mixed $orderId
     * @return array
     * @throws Exception
     */
    public function process_payment($orderId) : array
    {
        /* @var GoDaddyPaymentsGateway|null $parentGateway */
        $parentGateway = CorePaymentGateways::getManagedPaymentGatewayInstance('poynt');

        if (! $parentGateway) {
            throw new Exception(__('Cannot load the GoDaddy Payments gateway to process Apple Pay payment.', 'mwc-core'));
        }

        return $parentGateway->process_payment($orderId);
    }

    /**
     * Filters the order payment method title for orders paid with Apple Pay.
     *
     * @internal filter callback
     *
     * @param string|mixed $title
     * @param WC_Order|mixed $order
     * @return string|mixed
     */
    public function filterOrderPaymentMethodTitle($title, $order)
    {
        if ($order && is_a($order, 'WC_Order') && 'poynt' === $order->get_payment_method()) {
            try {
                $transaction = $this->getPaymentTransactionForOrder((int) $order->get_id());

                if ('poynt' === $transaction->getProviderName() && 'mwc_payments_apple_pay' === $transaction->getSource()) {
                    return sprintf('%s (Apple Pay - GoDaddy Payments)', $order->get_meta('_poynt_payment_paymentMethod_lastFour') ?? '');
                }
            } catch (Exception $exception) {
                return $title;
            }
        }

        return $title;
    }

    /**
     * Gets the payment transaction for a given order.
     *
     * @param int $orderId
     * @return AbstractTransaction|PaymentTransaction
     * @throws Exception
     */
    protected function getPaymentTransactionForOrder(int $orderId) : AbstractTransaction
    {
        return (new OrderTransactionDataStore('poynt'))->read($orderId, 'payment');
    }

    /**
     * Determines whether the gateway is available.
     *
     * Implements parent method. We don't need this to be available as a normal WooCommerce payment gateway.
     * @see ApplePayGateway::isActive()
     *
     * @return false
     */
    public function is_available() : bool
    {
        return false;
    }

    /**
     * Determines whether the gateway is active.
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive() : bool
    {
        return true === Configuration::get('features.apple_pay')
            && Onboarding::STATUS_CONNECTED === Onboarding::getStatus()
            && GoDaddyPaymentsGateway::isActive();
    }
}
