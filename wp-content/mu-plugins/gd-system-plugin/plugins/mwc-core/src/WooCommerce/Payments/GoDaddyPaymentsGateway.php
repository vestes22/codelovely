<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Events\BeforeCreateRefundEvent;
use GoDaddy\WordPress\MWC\Core\Events\BeforeCreateVoidEvent;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\BrowseExtensionsPromotionBlocksOverride;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\Notices;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\PaymentMethodsListTable;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\SetUpPaymentsTaskOverride;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\PaymentForm;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations\PreOrderIntegration;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations\SubscriptionsIntegration;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\AmericanExpressCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DinersClubCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DiscoverCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MaestroCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MastercardCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\VisaCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\VoidTransaction;
use WC_Order;

/**
 * GoDaddy Native payment Gateway.
 *
 * @since 2.10.0
 */
class GoDaddyPaymentsGateway extends AbstractPaymentGateway implements SubscriberContract
{
    /** Sends through sale and request for funds to be charged to cardholder's credit card. */
    const TRANSACTION_TYPE_CHARGE = 'charge';

    /** Sends through a request for funds to be "reserved" on the cardholder's credit card. A standard authorization is reserved for 2-5 days. Reservation times are determined by cardholder's bank. */
    const TRANSACTION_TYPE_AUTHORIZATION = 'authorization';

    /** @var string method title */
    public $method_title = 'GoDaddy Payments';

    /** @var PaymentForm */
    protected $paymentForm;

    /** @var string provider name. */
    protected $providerName = 'poynt';

    /** @var RefundTransaction the current transaction we are operating against */
    protected static $currentRefundTransaction;

    /** @var VoidTransaction the current transaction we are operating against */
    protected static $currentVoidTransaction;

    /**
     * Constructs the GoDaddy Payment Gateway.
     *
     * @since 2.10.0
     * @throws Exception
     */
    public function __construct()
    {
        $this->id = $this->providerName;
        $this->method_description = __('Accept credit/debit cards online and sell in person with secure checkout forms and smart terminals. Get paid fast with next-day deposits.', 'mwc-core');

        $this->view_transaction_url = StringHelper::trailingSlash(Poynt::getHubUrl()).'transactions/%s';

        $this->init_form_fields();
        $this->init_settings();

        $this->updateConfigurationFromSettings([
            'enabled'             => 'enabled',
            'capturePaidOrders'   => 'capture_paid_orders',
            'chargeVirtualOrders' => 'charge_virtual_orders',
            'detailedDecline'     => 'enable_detailed_decline_messages',
            'paymentMethods'      => 'enable_tokenization',
            'transactionType'     => 'transaction_type',
            'debugMode'           => 'debug_mode',
        ]);

        // add notices
        new Notices();
        new SetUpPaymentsTaskOverride();
        new BrowseExtensionsPromotionBlocksOverride();

        $this->setIntegrations();

        $this->paymentForm = new PaymentForm($this->providerName);

        parent::__construct();

        Register::action()
            ->setGroup('woocommerce_update_options_payment_gateways_'.$this->id)
            ->setHandler([$this, 'process_admin_options'])
            ->execute();
    }

    /**
     * Sets the gateway's integration handlers.
     *
     * @return self
     * @throws Exception
     */
    protected function setIntegrations()
    {
        // only load if saved payment methods are enabled
        if (Configuration::get("payments.{$this->providerName}.paymentMethods")) {
            $this->integrations[] = new SubscriptionsIntegration($this);
            $this->integrations[] = new PreOrderIntegration($this);
        }

        return $this;
    }

    /**
     * Determines if the gateway should be active for use.
     *
     * @return bool
     * @throws Exception
     */
    public static function isActive(): bool
    {
        // bail early if purposefully disabled
        if (! Configuration::get('payments.poynt.active')) {
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

        // otherwise adhere to the requirements for new users
        // TODO: we should look to move Woo methods like these under a WooCommerceConfiguration repository of sorts {@cwiseman 2021-05-28}
        return
            WC()->countries
            && 'US' === WC()->countries->get_base_country()
            && 'USD' === get_woocommerce_currency();
    }

    /**
     * Gets the transaction URL external link.
     *
     * @param WC_Order $order
     *
     * @return string
     * @throws Exception
     */
    public function get_transaction_url($order)
    {
        return add_query_arg('businessId', Poynt::getBusinessId(), parent::get_transaction_url($order));
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
            <?php echo PaymentMethodsListTable::getStatusHtml(Onboarding::getStatus()); ?>
        </h2>

        <p class="mwc-payments-godaddy-settings-description">
            <?php printf(
                esc_html__('Manage your GoDaddy Payments account settings in the %1$sPayments Hub%2$s', 'mwc-core'),
                '<a href="'.esc_url(add_query_arg('businessId', Poynt::getBusinessId(), StringHelper::trailingSlash(Poynt::getHubUrl()).'settings')).'" target="_blank">',
                ' <span class="dashicons dashicons-external"></span></a>'
            ); ?>
        </p>

        <table class="form-table"><?php echo $this->generate_settings_html($this->get_form_fields(), false); ?></table>
        <?php
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
            return parent::is_available() && Poynt::isConnected();
        } catch (Exception $exception) {
            // TODO: log the error {@cwiseman 2021-05-21}
            return false;
        }
    }

    /**
     * Initializes the WooCommerce settings.
     *
     * @since 2.10.0
     */
    public function init_settings()
    {
        parent::init_settings();

        $this->has_fields = true;

        $this->title = $this->settings['title'] ?? $this->getDefaultTitle();
        $this->description = $this->settings['description'] ?? $this->getDefaultDescription();
    }

    /**
     * Renders the payment fields.
     *
     * @since 2.10.0
     */
    public function payment_fields()
    {
        ?>
        <p><?php echo wp_kses_post($this->get_description()); ?></p>
        <?php

        $this->paymentForm->render();
    }

    /**
     * Gets the payment method icon, for display at checkout.
     *
     * @since 2.10.0
     *
     * @return string
     */
    public function get_icon()
    {
        $imageUrls = [];

        foreach ($this->getAcceptedCardBrands() as $brand) {
            try {
                $imageUrls[$brand->getName()] = WordPressRepository::getAssetsUrl("images/payments/cards/{$brand->getName()}.svg");
            } catch (Exception $exception) {
            }
        }

        if (empty($imageUrls)) {
            return '';
        }

        ob_start(); ?>
        <div class="mwc-payments-gateway-card-icons">

            <?php foreach ($imageUrls as $brandName => $imageUrl) : ?>
                <img src="<?php echo esc_url($imageUrl); ?>" alt="<?php echo esc_attr($brandName); ?>" width="40" height="25" style="width: 40px; height: 25px;" />
            <?php endforeach; ?>

        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets a card payment method to add.
     *
     * @since 2.10.0
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    protected function getPaymentMethodForAdd(): AbstractPaymentMethod
    {
        $nonce = StringHelper::sanitize((string) filter_input(INPUT_POST, 'mwc-payments-'.$this->providerName.'-payment-nonce', FILTER_SANITIZE_STRING));

        $paymentMethod = (new CardPaymentMethod())->setRemoteId($nonce);

        if ($currentUser = User::getCurrent()) {
            $paymentMethod->setCustomerId($currentUser->getId());
        }

        return $paymentMethod;
    }

    /**
     * Returns true if $event is a BeforeCreateRefundEvent or BeforeCreateVoidEvent with a transaction
     * with provider name equal to this gateway's provider name.
     *
     * @param EventContract $event
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return ($event instanceof BeforeCreateRefundEvent || $event instanceof BeforeCreateVoidEvent)
            && $event->getTransaction()->getProviderName() == $this->providerName;
    }

    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @return void
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        if ($event instanceof BeforeCreateRefundEvent) {
            static::$currentRefundTransaction = $event->getTransaction();
        }
        if ($event instanceof BeforeCreateVoidEvent) {
            static::$currentVoidTransaction = $event->getTransaction();
        }
    }

    /**
     * Gets a transaction for refund.
     *
     * @param WC_Order $order
     *
     * @return RefundTransaction
     * @throws Exception
     */
    protected function getTransactionForRefund(WC_Order $order): RefundTransaction
    {
        if (static::$currentRefundTransaction) {
            return static::$currentRefundTransaction;
        }

        $transaction = parent::getTransactionForRefund($order);

        // if there is a capture transaction for this order, use the capture's remote ID instead of the authorization
        $captureTransaction = (new OrderTransactionDataStore($this->providerName))->read($order->get_id(), 'capture');

        if ($remoteParentId = $captureTransaction->getRemoteId()) {
            $transaction->setRemoteParentId($remoteParentId);
        }

        return $transaction;
    }

    /**
     * Gets a Transaction for Void.
     *
     * @param WC_Order|null $order
     * @return VoidTransaction
     * @throws Exception
     */
    public function getTransactionForVoid(WC_Order $order) : VoidTransaction
    {
        return static::$currentVoidTransaction ?: parent::getTransactionForVoid($order);
    }

    /**
     * Initialise settings form fields.
     *
     * @since 2.10.0
     */
    public function init_form_fields()
    {
        $paidStatuses = array_map('ucfirst', wc_get_is_paid_statuses());

        // TODO: move this to a helper method in ArrayHelper {@cwiseman}
        if (count($paidStatuses) > 1) {
            $lastItem = array_pop($paidStatuses);

            array_push($paidStatuses, trim("or {$lastItem}"));

            // only use a comma if needed and no separator was passed
            if (count($paidStatuses) < 3) {
                $separator = ' ';
            } else {
                $separator = ', ';
            }

            $paidStatuses = implode($separator, $paidStatuses);
        }

        $this->form_fields = [
            'enabled'                          => [
                'title'    => esc_html__('Enable', 'mwc-core'),
                'type'     => 'checkbox',
                'label' => esc_html__('Enable to add the payment method to your checkout.', 'mwc-core'),
                'default'  => 'no',
                'disabled' => ! Onboarding::canEnablePaymentGateway(Onboarding::getStatus()),
            ],
            'title'                            => [
                'title'    => esc_html__('Title in Checkout', 'mwc-core'),
                'type'     => 'text',
                'desc_tip' => esc_html__('Payment method title that the customer will see during checkout.', 'mwc-core'),
                'default'  => $this->getDefaultTitle(),
            ],
            'description'                      => [
                'title'    => esc_html__('Description in Checkout', 'mwc-core'),
                'type'     => 'textarea',
                'desc_tip' => esc_html__('Payment method description that the customer will see during checkout.', 'mwc-core'),
                'default'  => $this->getDefaultDescription(),
            ],
            'transaction_type'                 => [
                'title'    => esc_html__('Transaction Type', 'mwc-core'),
                'type'     => 'select',
                'desc_tip' => esc_html__('Select how transactions should be processed. Charge submits all transactions for settlement, Authorization simply authorizes the order total for capture later.', 'mwc-core'),
                'default'  => self::TRANSACTION_TYPE_CHARGE,
                'options'  => [
                    self::TRANSACTION_TYPE_CHARGE        => esc_html_x('Charge', 'noun, credit card transaction type', 'mwc-core'),
                    self::TRANSACTION_TYPE_AUTHORIZATION => esc_html_x('Authorization', 'credit card transaction type', 'mwc-core'),
                ],
            ],
            'charge_virtual_orders'            => [
                'title'       => esc_html__('Charge Virtual-Only Orders', 'mwc-core'),
                'type'        => 'checkbox',
                'description' => esc_html__('If the order contains exclusively virtual items, enable this to immediately charge, rather than authorize, the transaction.', 'mwc-core'),
                'default'     => 'yes',
            ],
            'capture_paid_orders'              => [
                'title'   => esc_html__('Capture Paid Orders', 'mwc-core'),
                'type'    => 'checkbox',
                'default' => 'yes',
                'description' => sprintf(
                    __('Automatically capture orders when they are changed to %s.', 'mwc-core'),
                    esc_html(! empty($paidStatuses) ? $paidStatuses : __('a paid status', 'mwc-core'))
                ),
            ],
            'accepted_card_brands'              => [
                'title'    => esc_html__('Accepted Card Logos', 'mwc-core'),
                'type'     => 'multiselect',
                'desc_tip' => esc_html__('These are the card logos that are displayed to customers as accepted during checkout.', 'mwc-core'),
                'description' => sprintf(
                    /* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
                    __('This setting %1$sdoes not%2$s change which card types the gateway will accept. Accepted cards are configured from your payment processor account.', 'mwc-core'),
                    '<strong>',
                    '</strong>'
                ),
                'default'  => array_keys($this->getAvailableCardBrands()),
                'class'    => 'wc-enhanced-select',
                'options'  => array_map(function ($brand) {
                    return $brand->getLabel();
                }, $this->getAvailableCardBrands()),
            ],
            'enable_tokenization'              => [
                'title'   => esc_html__('Saved Cards', 'mwc-core'),
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable customers to securely save their payment cards to their account for future checkout.', 'mwc-core'),
                'default' => 'no',
            ],
            'enable_detailed_decline_messages' => [
                'title'   => esc_html__('Detailed Decline Messages', 'mwc-core'),
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable detailed decline messages for customers during checkout rather than a generic decline message.', 'mwc-core'),
                'default' => 'yes',
            ],
            'debug_mode'                       => [
                'title'    => esc_html__('Debug Mode', 'mwc-core'),
                'type'     => 'select',
                'desc_tip' => esc_html__('Show Detailed Error Messages and API requests/responses on the checkout page and/or save them to the debug log', 'mwc-core'),
                'default'  => self::DEBUG_MODE_OFF,
                'options'  => [
                    self::DEBUG_MODE_OFF      => esc_html__('Off', 'mwc-core'),
                    self::DEBUG_MODE_CHECKOUT => esc_html__('Show on Checkout Page', 'mwc-core'),
                    self::DEBUG_MODE_LOG      => esc_html__('Save to Log', 'mwc-core'),
                    self::DEBUG_MODE_BOTH     => esc_html__('Both', 'mwc-core'),
                ],
            ],
        ];
    }

    /**
     * Gets the accepted card brands.
     *
     * @since 2.10.0
     *
     * @return CardBrandContract[]
     */
    protected function getAcceptedCardBrands(): array
    {
        $acceptedBrandNames = (array) $this->get_option('accepted_card_brands', []);
        $availableBrands = $this->getAvailableCardBrands();

        return array_intersect_key($availableBrands, array_flip($acceptedBrandNames));
    }

    /**
     * Gets available card types.
     *
     * @since 2.10.0
     *
     * @return CardBrandContract[]
     */
    protected function getAvailableCardBrands(): array
    {
        $brands = [];
        $brandObjects = [
            new AmericanExpressCardBrand(),
            new DinersClubCardBrand(),
            new DiscoverCardBrand(),
            new MaestroCardBrand(),
            new MastercardCardBrand(),
            new VisaCardBrand(),
        ];

        /** @var CardBrandContract $brandObject */
        foreach ($brandObjects as $brandObject) {
            $brands[$brandObject->getName()] = $brandObject;
        }

        return $brands;
    }

    /**
     * Gets the default gateway description.
     *
     * This is used to fill the Description setting for display at checkout.
     *
     * @since 2.10.0
     *
     * @return string
     */
    private function getDefaultDescription(): string
    {
        return esc_html__('Pay securely with your credit/debit card.', 'mwc-core');
    }

    /**
     * Gets the default gateway title.
     *
     * This is used to fill the Title setting for display at checkout.
     *
     * @since 2.10.0
     *
     * @return string
     */
    private function getDefaultTitle(): string
    {
        return esc_html__('Credit/Debit Card', 'mwc-core');
    }

    /**
     * Processes a payment transaction.
     *
     * Overridden since Poynt requires tokenizing nonces before payment.
     *
     * @param PaymentTransaction $transaction
     *
     * @return PaymentTransaction
     * @throws Exception
     */
    public function processPayment(PaymentTransaction $transaction): PaymentTransaction
    {
        $paymentMethod = $transaction->getPaymentMethod();

        // if there is a payment method but it's not been made permanent, make it permanent
        if (! $transaction->shouldTokenize() && $paymentMethod && ! $paymentMethod->getId() && ! $paymentMethod->getCreatedAt()) {
            $paymentMethod = $this->getProvider()->paymentMethods()->create($paymentMethod);
        }

        $transaction->setPaymentMethod($paymentMethod);

        return parent::processPayment($transaction);
    }
}
