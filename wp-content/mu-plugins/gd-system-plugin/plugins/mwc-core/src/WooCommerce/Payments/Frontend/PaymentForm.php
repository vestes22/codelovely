<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\PaymentMethodDataStore;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\AbstractPaymentMethodView;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\PaymentForm\CardPaymentMethodView;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use WC_Payment_Token;
use WC_Payment_Tokens;

/**
 * The payment form class.
 *
 * Used for rendering the payment form, along with saved payment methods, at checkout and in My Account.
 */
class PaymentForm
{
    /** @var bool whether to allow tokenization */
    protected $allowTokenization = false;

    /** @var int the ID for the customer's default payment method */
    protected $defaultPaymentMethodId;

    /** @var string the data store used to retrieve payment methods */
    protected $paymentMethodDataStore = PaymentMethodDataStore::class;

    /** @var string[] the payment method classes and their associated view classes */
    protected $paymentMethodViews = [
        CardPaymentMethod::class => CardPaymentMethodView::class,
    ];

    /** @var AbstractPaymentMethod[] */
    protected $paymentMethods = [];

    /** @var string payment provider name */
    protected $providerName;

    /**
     * PaymentForm constructor.
     *
     * @param string $providerName
     *
     * @throws Exception
     */
    public function __construct(string $providerName)
    {
        $this->providerName = $providerName;
        $this->allowTokenization = Configuration::get("payments.{$this->providerName}.paymentMethods", false);

        $this->registerHooks();

        if ($this->allowTokenization) {
            $this->getPaymentMethods();
        }
    }

    /**
     * Registers the action & filter hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('wp_enqueue_scripts')
            ->setHandler([$this, 'enqueueStyles'])
            ->execute();
    }

    /**
     * Enqueues the styles.
     *
     * @internal
     *
     * @throws Exception
     */
    public function enqueueStyles()
    {
        Enqueue::style()
            ->setHandle('mwc-payments-payment-form')
            ->setSource(WordPressRepository::getAssetsUrl('css/payment-form.css'))
            ->execute();
    }

    /**
     * Gets the available payment methods.
     *
     * @return AbstractPaymentMethod[]
     */
    protected function getPaymentMethods() : array
    {
        if (! empty($this->paymentMethods)) {
            return $this->paymentMethods;
        }

        $dataStore = $this->getPaymentMethodDataStore($this->providerName);

        foreach ($this->getWooCommerceTokens() as $wooCommerceToken) {
            try {
                if ($wooCommerceToken->is_default()) {
                    $this->defaultPaymentMethodId = $wooCommerceToken->get_id();
                }

                $paymentMethod = $dataStore->read($wooCommerceToken->get_id());

                $this->paymentMethods[$paymentMethod->getId()] = $paymentMethod;
            } catch (Exception $exception) {
            }
        }

        return $this->paymentMethods;
    }

    /**
     * Gets data store instance for the payment method of the given provider.
     *
     * @param string $providerName
     * @return PaymentMethodDataStore
     */
    protected function getPaymentMethodDataStore(string $providerName) : PaymentMethodDataStore
    {
        return new PaymentMethodDataStore($providerName);
    }

    /**
     * Gets the current customer's WooCommerce tokens.
     *
     * @return WC_Payment_Token[]
     */
    protected function getWooCommerceTokens() : array
    {
        $currentUser = User::getCurrent();

        if (! $currentUser) {
            return [];
        }

        return WC_Payment_Tokens::get_customer_tokens($currentUser->getId(), $this->providerName);
    }

    /**
     * Determines whether the form should force tokenization.
     *
     * @return bool
     */
    protected function forceTokenization() : bool
    {
        return (bool) apply_filters('mwc_payments_force_tokenization', false, $this->providerName) || is_add_payment_method_page();
    }

    /**
     * Renders the full payment form.
     */
    public function render()
    {
        if ($this->tokenizationAllowed() && ! empty($this->getPaymentMethods()) && ! is_add_payment_method_page()) {
            $this->renderPaymentMethods();
        } ?>
        <div class="mwc-payments-new-payment-method-form mwc-payments-<?php echo esc_attr($this->providerName); ?>-new-payment-method-form">
            <fieldset class="mwc-payments-payment-form mwc-payments-<?php echo esc_attr($this->providerName); ?>-payment-form">
                <?php $this->renderPaymentFields(); ?>
                <?php $this->renderTokenizeField(); ?>
            </fieldset>
        </div>
        <?php
    }

    /**
     * Renders the existing payment methods.
     */
    protected function renderPaymentMethods()
    {
        ?>
        <p class="form-row form-row-wide">

            <a
                class="button mwc-payments-manage-payment-methods"
                href="<?php echo esc_url(wc_get_account_endpoint_url('payment-methods')); ?>"
            >
                <?php esc_html_e('Manage Payment Methods', 'mwc-core'); ?>
            </a>

            <?php foreach ($this->getPaymentMethods() as $paymentMethod) :

                $view = ArrayHelper::get($this->paymentMethodViews, get_class($paymentMethod));

        if ($view && $paymentMethodView = $this->getPaymentMethodViewInstance($view, $paymentMethod)) {
            $paymentMethodView->render($paymentMethod->getId() === $this->defaultPaymentMethodId);
        } ?>
                <br>

            <?php endforeach; ?>

            <input
                type="radio"
                id="mwc-payments-<?php echo esc_attr($this->providerName); ?>-use-new-payment-method"
                name="mwc-payments-<?php echo esc_attr($this->providerName); ?>-payment-method-id"
                class="mwc-payments-use-new-payment-method mwc-payments-poynt-payment-method"
                style="width:auto; margin-right: .5em;"
                value=""
                <?php checked(! $this->defaultPaymentMethodId); ?>
            />
            <label
                style="display:inline;"
                for="mwc-payments-<?php echo esc_attr($this->providerName); ?>-use-new-payment-method"
            >
                <?php esc_html_e('Use a new card', 'mwc-core'); ?>
            </label>

        </p>
        <div class="clear"></div>
        <?php
    }

    /**
     * Gets an instance of given payment method view.
     *
     * @param string $viewClass
     * @param AbstractPaymentMethod $paymentMethod
     * @return AbstractPaymentMethodView|null
     */
    protected function getPaymentMethodViewInstance(string $viewClass, AbstractPaymentMethod $paymentMethod)
    {
        if (is_subclass_of($viewClass, AbstractPaymentMethodView::class)) {
            return new $viewClass($paymentMethod);
        }

        return null;
    }

    /**
     * Renders the regular payment fields.
     */
    protected function renderPaymentFields()
    {
    }

    /**
     * Renders the tokenize payment field.
     */
    protected function renderTokenizeField()
    {
        $tokenizeFieldId = 'mwc-payments-'.$this->providerName.'-tokenize-payment-method';

        if ($this->forceTokenization()) {
            ?>
            <input
                type="hidden"
                id="<?php echo esc_attr($tokenizeFieldId); ?>"
                name="<?php echo esc_attr($tokenizeFieldId); ?>"
                class="mwc-payments-tokenize-payment-method"
                value="true"
            >
            <?php
        } elseif ($this->tokenizationAllowed()) {
            ?>
            <p class="form-row">
                <input
                    type="checkbox"
                    id="<?php echo esc_attr($tokenizeFieldId); ?>"
                    name="<?php echo esc_attr($tokenizeFieldId); ?>"
                    class="mwc-payments-tokenize-payment-method"
                    value="true"
                    style="width:auto;"
                >
                <label
                    for="<?php echo esc_attr($tokenizeFieldId); ?>"
                    style="display:inline;"
                >
                    <?php esc_html_e('Securely Save to Account', 'mwc-core'); ?>
                </label>
            </p>
            <div class="clear">
            <?php
        }
    }

    /**
     * Determines if tokenization is allowed.
     *
     * @return bool
     */
    protected function tokenizationAllowed() : bool
    {
        // tokenization is allowed if tokenization is enabled on the gateway
        $tokenizationAllowed = $this->allowTokenization;

        if ($tokenizationAllowed && ! is_user_logged_in()) {
            if (is_checkout_pay_page()) {

                // on the pay page there is no way of creating an account, so disallow tokenization for guest customers
                $tokenizationAllowed = false;
            } elseif (is_checkout()) {

                // on the checkout page, only allow if account creation during checkout is enabled
                $tokenizationAllowed = WC()->checkout()->is_registration_enabled();
            }
        }

        return $tokenizationAllowed;
    }
}
