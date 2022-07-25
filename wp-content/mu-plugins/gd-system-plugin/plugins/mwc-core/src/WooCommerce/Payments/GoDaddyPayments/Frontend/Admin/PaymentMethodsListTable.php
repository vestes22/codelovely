<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin;

use Exception;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Enqueue\Types\EnqueueScript;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\AbstractPaymentGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayInPersonGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;
use WC_Payment_Gateway;

/**
 * The payment methods list table handler.
 */
class PaymentMethodsListTable
{
    /** @var string name for the action column */
    protected static $actionColumnName = 'onboarding-action';

    /** @var string name for the status column */
    protected static $onboardingStatusColumnName = 'onboarding-status';

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
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
            ->setGroup('admin_enqueue_scripts')
            ->setCondition([$this, 'shouldEnqueueScripts'])
            ->setHandler([$this, 'enqueueScripts'])
            ->execute();

        Register::action()
            ->setGroup('admin_menu')
            ->setHandler([$this, 'addHubMenuItem'])
            ->execute();

        Register::action()
            ->setGroup('admin_footer')
            ->setHandler([$this, 'renderOnboardingModal'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_payment_gateways_setting_columns')
            ->setArgumentsCount(1)
            ->setHandler([$this, 'addColumns'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_payment_gateways_setting_column_'.static::$actionColumnName)
            ->setArgumentsCount(1)
            ->setHandler([$this, 'renderActionCell'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_payment_gateways_setting_column_'.static::$onboardingStatusColumnName)
            ->setArgumentsCount(1)
            ->setHandler([$this, 'renderOnboardingStatusCell'])
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_gateway_method_description')
            ->setArgumentsCount(2)
            ->setHandler([$this, 'renderDescriptionCell'])
            ->execute();
    }

    /**
     * Adds the Status and Action columns to the table.
     *
     * @param mixed $columns
     *
     * @return mixed
     */
    public function addColumns($columns)
    {
        if (! ArrayHelper::accessible($columns)) {
            return $columns;
        }

        $newColumns = [];

        foreach ($columns as $name => $label) {
            if ('action' === $name) {
                $newColumns[static::$onboardingStatusColumnName] = __('Status', 'mwc-core');
                $newColumns[static::$actionColumnName] = '';
                continue;
            }

            $newColumns[$name] = $label;
        }

        return $newColumns;
    }

    /**
     * Adds a menu item to the WordPress menu linking to the Hub.
     *
     * @internal
     *
     * @throws Exception
     */
    public function addHubMenuItem()
    {
        global $submenu;

        // bail if the payment gateway cannot be enabled
        if (! Onboarding::canEnablePaymentGateway(Onboarding::getStatus())) {
            return;
        }

        // bail if the WooCommerce menu item is not displayed for the current user
        if (! $submenu || ! ArrayHelper::exists($submenu, 'woocommerce') || ! current_user_can('manage_woocommerce')) {
            return;
        }

        $submenu['woocommerce'][] = [
            '<span style="white-space:nowrap;">'.__('GoDaddy Payments', 'mwc-core').'</span><span class="dashicons dashicons-external" style="width:14px;height:14px;font-size:14px;padding-top:2px;"></span>', // tweak the icon to smaller to fit the container
            'manage_woocommerce',
            add_query_arg('businessId', Poynt::getBusinessId(), Poynt::getHubUrl()),
        ];
    }

    /**
     * Enqueues the scripts.
     *
     * @internal
     *
     * @throws Exception
     */
    public function enqueueScripts()
    {
        $onboardingStatus = Onboarding::getStatus();
        $paymentMethods = [
            [
                'gatewayId'                 => 'poynt',
                'status'                    => $onboardingStatus,
                'allowButton'               => ! ArrayHelper::contains([Onboarding::STATUS_SUSPENDED, Onboarding::STATUS_PENDING], $onboardingStatus),
                'allowEnable'               => Onboarding::canEnablePaymentGateway($onboardingStatus),
                'allowManage'               => Onboarding::canManagePaymentGateway($onboardingStatus),
                'setupIntentAction'         => OnboardingEventsProducer::ACTION_SETUP_INTENT,
                'setupIntentNonce'          => wp_create_nonce(OnboardingEventsProducer::ACTION_SETUP_INTENT),
                'removePaymentMethodAction' => OnboardingEventsProducer::ACTION_REMOVE_PAYMENT_METHOD,
                'removePaymentMethodNonce'  => wp_create_nonce(OnboardingEventsProducer::ACTION_REMOVE_PAYMENT_METHOD),
            ],
        ];

        if (Onboarding::STATUS_CONNECTED === $onboardingStatus) {
            $paymentMethods[] = [
                'gatewayId'     => 'godaddy-payments-payinperson',
                'status'        => $onboardingStatus,
                'allowButton'   => ! ArrayHelper::contains([Onboarding::STATUS_SUSPENDED, Onboarding::STATUS_PENDING, Onboarding::STATUS_TERMINATED], $onboardingStatus),
                'allowEnable'   => GoDaddyPayInPersonGateway::canEnablePaymentGateway(),
                'allowManage'   => Onboarding::canManagePaymentGateway($onboardingStatus),
            ];
        }

        Enqueue::style()
            ->setHandle('mwc-payments-payment-methods')
            ->setSource(WordPressRepository::getAssetsUrl('css/payment-methods.css'))
            ->execute();

        EnqueueScript::script()
            ->setHandle('mwc-payments-payment-methods')
            ->setSource(WordPressRepository::getAssetsUrl('js/payments/payment-methods.js'))
            ->setDependencies([
                'backbone',
                'wc-backbone-modal',
                'jquery',
            ])
            ->attachInlineScriptObject('MWCPaymentsPaymentMethods')
            ->attachInlineScriptVariables($paymentMethods)
            ->execute();
    }

    /**
     * Gets the button label.
     *
     * @param string $status
     * @return string
     * @throws Exception
     */
    protected function getButtonLabel(string $status) : string
    {
        switch ($status) {
            case '':
            case Onboarding::STATUS_DISCONNECTED:
                return __('Set up', 'mwc-core');
            case Onboarding::STATUS_INCOMPLETE:
                return __('Resume', 'mwc-core');
            case Onboarding::STATUS_DECLINED:
            case Onboarding::STATUS_TERMINATED:
                return __('Remove', 'mwc-core');
            default:
                return __('Manage', 'mwc-core');
        }
    }

    /**
     * Gets the HTML for displaying the given status.
     *
     * @param string $status
     * @param object $gateway
     * @return string
     * @throws Exception
     */
    public static function getStatusHtml(string $status, $gateway = null) : string
    {
        if (Onboarding::STATUS_CONNECTED === $status && $gateway && $gateway instanceof GoDaddyPayInPersonGateway && Poynt::hasPoyntSmartTerminalActivated()) {
            $label = __('Connected', 'mwc-core');
            $tooltip = __('Connected to your terminal to Sell in Person.', 'mwc-core');
        } else {
            switch ($status) {
                case '':
                    $label = __('Recommended', 'mwc-core');
                    $tooltip = $gateway && $gateway instanceof GoDaddyPayInPersonGateway
                        ? __('', 'mwc-core')
                        : __('2.3% + 30¢ per online store transaction.', 'mwc-core');
                    break;
                case Onboarding::STATUS_CONNECTED:
                    $label = $gateway instanceof GoDaddyPayInPersonGateway
                        ? __('New', 'mwc-core')
                        : __('Connected', 'mwc-core');
                    $tooltip = $gateway instanceof GoDaddyPayInPersonGateway
                        ? __('Connect your terminal to Sell in Person.', 'mwc-core')
                        : __('Connected to your GoDaddy Payments account.', 'mwc-core');
                    break;
                case Onboarding::STATUS_CONNECTING:
                    $label = __('Connecting', 'mwc-core');
                    $tooltip = __('This can take a few minutes. Please refresh to check.', 'mwc-core');
                    break;
                case Onboarding::STATUS_DECLINED:
                    $label = __('Not available', 'mwc-core');
                    $tooltip = __('Thank you for applying, but we are unable to provide payment processing for your business.', 'mwc-core');
                    break;
                case Onboarding::STATUS_DISCONNECTED:
                    $label = __('Disconnected', 'mwc-core');
                    $tooltip = __('Your account has been closed.', 'mwc-core');
                    break;
                case Onboarding::STATUS_INCOMPLETE:
                    $label = __('Incomplete', 'mwc-core');
                    $tooltip = __('Click Resume to complete your application.', 'mwc-core');
                    break;
                case Onboarding::STATUS_NEEDS_ATTENTION:
                case Onboarding::STATUS_SUSPENDED:
                    $label = __('Needs attention', 'mwc-core');
                    $tooltip = __('Please check your email for next steps.', 'mwc-core');
                    break;
                case Onboarding::STATUS_PENDING:
                    $label = __('Pending', 'mwc-core');
                    $tooltip = __('Please check your email for next steps.', 'mwc-core');
                    break;
                case Onboarding::STATUS_TERMINATED:
                    $label = __('Not available', 'mwc-core');
                    $tooltip = __('Your GoDaddy Payments account has been terminated.', 'mwc-core');
                    break;
                default:
                    return '';
            }
        }

        return '<mark class="mwc-payments-godaddy-sip-status tips '.esc_attr(strtolower($status)).'" data-tip="'.esc_attr($tooltip).'">'.esc_html($label).'</mark>';
    }

    /**
     * Renders the action column's cell.
     *
     * @internal
     *
     * @param mixed $gateway
     * @throws Exception
     */
    public function renderActionCell($gateway)
    {
        if (! $gateway instanceof WC_Payment_Gateway) {
            return;
        }

        $title = $gateway->get_method_title() ?: $gateway->get_title();
        $url = admin_url('admin.php?page=wc-settings&tab=checkout&section='.strtolower($gateway->id));
        $classes = [
            'button',
            'alignright',
        ];

        if ($gateway instanceof GoDaddyPaymentsGateway) {
            $status = Onboarding::getStatus();

            $classes[] = $status ? strtolower($status) : 'start';

            $label = $this->getButtonLabel($status);

            if (! $status || Onboarding::STATUS_INCOMPLETE === $status) {
                $url = $this->getOnboardingStartUrl($status);
            } elseif (ArrayHelper::contains([Onboarding::STATUS_DECLINED, Onboarding::STATUS_TERMINATED], $status)) {
                $classes[] = 'remove';
            }
        } elseif (wc_string_to_bool($gateway->enabled)) {
            $ariaLabel = sprintf(__('Manage the "%s" payment method', 'woocommerce'), $title);
            $label = __('Manage', 'woocommerce');
        } else {
            $ariaLabel = sprintf(__('Set up the "%s" payment method', 'woocommerce'), $title);
            $label = __('Set up', 'woocommerce');
        } ?>
        <td class="<?php echo esc_attr(static::$actionColumnName); ?>" width="1%">
            <a class="<?php echo esc_attr(implode(' ', $classes)); ?>" aria-label="<?php echo ! empty($ariaLabel) ? esc_attr($ariaLabel) : ''; ?>" href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($label); ?>
            </a>
        </td>
        <?php
    }

    /**
     * Renders the status column cell.
     *
     * @internal callback
     *
     * @param WC_Payment_Gateway|AbstractPaymentGateway|mixed $gateway
     * @throws Exception
     */
    public function renderOnboardingStatusCell($gateway)
    {
        ?>
        <td class="<?php echo esc_attr(static::$onboardingStatusColumnName); ?>" width="1%">
            <?php if ($gateway instanceof GoDaddyPaymentsGateway || $gateway  instanceof ApplePayGateway || $gateway instanceof GoDaddyPayInPersonGateway) : ?>
                <?php echo static::getStatusHtml(Onboarding::getStatus(), $gateway); // TODO: label/react app {@cwiseman 2021-05-19}?>
            <?php endif; ?>
        </td>
        <?php
    }

    /**
     * Modifies the description cell to add Learn More info.
     *
     * @internal
     *
     * @param mixed $description
     * @param mixed $gateway
     * @return string|null
     * @throws Exception
     */
    public function renderDescriptionCell($description, $gateway)
    {
        $html = wp_kses_post($description);

        if ($gateway instanceof GoDaddyPaymentsGateway && Onboarding::getStatus() === '') {
            /* translators: Placeholders: %1$s - <a> tag for the GoDaddy MWC Care mailto link, %2$s - </a> tag, %3$s - <img> tag with email icon */
            $questionText = sprintf(
                __('Have a question? %1$sAsk the GoDaddy Team%3$s%2$s', 'mwc-core'),
                '<a href="'.esc_url('mailto:mwccare@godaddy.com?subject="GoDaddy Payments Signup Question"').'" target="_blank" style="text-decoration:underline">',
                '</a>',
                '<img src="'.esc_url(WordPressRepository::getAssetsUrl('images/mail-icon.svg')).'" alt="'.esc_attr__('E-mail icon', 'mwc-core').'"/>'
            );

            $html .= '<div class="gd-payments-learn-more">'.
                     '<img class="gd-logo" src="'.esc_url(WordPressRepository::getAssetsUrl('images/branding/gd-icon.svg')).'" alt="'.esc_attr__('Provided by GoDaddy', 'mwc-core').'"/>'.
                     $questionText.
                     '</div>';
        }

        return $html;
    }

    /**
     * Renders the onboarding modal.
     *
     * @internal
     */
    public function renderOnboardingModal()
    {
        ?>
        <script type="text/template" id="tmpl-mwc-payments-godaddy-onboarding-start">
            <div class="wc-backbone-modal mwc-payments-godaddy-onboarding-start">
                <div class="wc-backbone-modal-content">
                    <section class="wc-backbone-modal-main" role="main">
                        <header class="wc-backbone-modal-header">
                            <h1><?php esc_html_e('Set up GoDaddy Payments', 'mwc-core'); ?></h1>
                            <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                                <span class="screen-reader-text"><?php esc_html_e('Close modal panel', 'woocommerce-customer-order-csv-export'); ?></span>
                            </button>
                        </header>
                        <article>
                            <div class="description">
                                <?php esc_html_e('You\'re about to open GoDaddy Payments account signup. In just a few minutes, you\'ll return here to enable secure payments in your checkout.', 'mwc-core'); ?>
                            </div>
                            <div class="details">
                                <div class="callout">
                                    <?php esc_html_e('Connect to GoDaddy Payments to quickly and easily accept all major credit cards with no setup fees or contracts.', 'mwc-core'); ?>
                                </div>
                                <div class="pricing">
                                    <span class="cost"><?php esc_html_e('2.3% + 30¢', 'mwc-core'); ?></span>
                                    <?php esc_html_e('per online store transaction', 'mwc-core'); ?>
                                </div>
                                <div class="clear"></div>
                            </div>
                        </article>
                        <footer>
                            <div class="inner">
                                <a href="#" id="btn-cancel" class="button button-clean modal-close"><?php esc_html_e('Cancel', 'mwc-core'); ?></a>
                                <a href="<?php echo esc_url($this->getOnboardingStartUrl()); ?>" class="button button-large onboarding-start"><?php esc_html_e('Set up GoDaddy Payments', 'mwc-core'); ?></a>
                            </div>
                        </footer>
                    </section>
                </div>
            </div>
            <div class="wc-backbone-modal-backdrop modal-close"></div>
        </script>
        <?php
    }

    /**
     * Determines if the scripts should be enqueued.
     *
     * @internal
     *
     * @return bool
     */
    public function shouldEnqueueScripts() : bool
    {
        return 'wc-settings' === ArrayHelper::get($_GET, 'page') && 'checkout' === ArrayHelper::get($_GET, 'tab');
    }

    /**
     * Gets the URL to kick off or resume onboarding.
     *
     * @param string $status the current onboarding status
     * @return string
     */
    protected function getOnboardingStartUrl(string $status = '') : string
    {
        return OnboardingEventsProducer::getOnboardingStartUrl(Onboarding::STATUS_INCOMPLETE === $status ? 'payment_method_resume_button' : 'onboarding_modal_setup_button');
    }
}
