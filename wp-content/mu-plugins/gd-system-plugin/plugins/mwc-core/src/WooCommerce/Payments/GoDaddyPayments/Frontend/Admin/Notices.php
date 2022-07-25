<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Admin\Notices as AdminNotices;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;
use WC_Shipping_Zones;

/**
 * Class Notices.
 *
 * TODO: consider converting this class into a general notice handler (rendering and Ajax) for core notices {@wvega 2021-05-28}
 */
class Notices
{
    /** @var string action used to dismiss a notice */
    const ACTION_DISMISS_NOTICE = 'mwc_dismiss_notice';

    /** @var string path for the GoDaddy Payments plugin */
    const GODADDY_PAYMENTS_PLUGIN_PATH = 'godaddy-payments/godaddy-payments.php';

    /** @var array sections to display GoDaddy Payment Recommendation */
    const GDP_RECOMMENDATION_SECTIONS = ['local_pickup_plus', 'cod'];

    /** @var array sections to display GoDaddy Payment Recommendation for Sell in Person */
    const GDP_SIP_RECOMMENDATION_SECTIONS = ['local_pickup_plus', 'cod'];

    /** @var array tabs to display GoDaddy Payment Recommendation */
    const GDP_RECOMMENDATION_TABS = ['shipping'];

    /** @var array tabs to display GoDaddy Payment SIP Recommendation */
    const GDP_SIP_RECOMMENDATION_TABS = ['shipping'];

    /** @var string WC Local Pickup Shipping Method id */
    const WC_LOCAL_PICKUP = 'local_pickup';

    /** @var array registered admin notices */
    protected $notices = [];

    /**
     * Notices constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->registerHooks();
    }

    /**
     * Registers the hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'registerNotices'])
            ->execute();

        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'renderNotices'])
            ->execute();
    }

    /**
     * Renders the notices.
     *
     * @throws Exception
     */
    public function renderNotices()
    {
        if (! $user = User::getCurrent()) {
            return;
        }

        foreach ($this->notices as $data) {
            if (! $this->shouldRenderNotice($user, $data)) {
                continue;
            }

            $this->renderNotice($data);
        }
    }

    /**
     * Determines whether a notice should be rendered for the given user.
     *
     * @param User $user a user object
     * @param array $data notice data
     * @return bool
     */
    public function shouldRenderNotice(User $user, array $data): bool
    {
        // bail if notice is not dismissible or if the notice was not dismissed by the user
        return ! ArrayHelper::get($data, 'dismissible', true)
            || ! AdminNotices::isNoticeDismissed($user, ArrayHelper::get($data, 'id', ''));
    }

    /**
     * Renders a notice.
     *
     * @param array $data
     * @throws Exception
     */
    protected function renderNotice(array $data)
    {
        if (empty($data['message'])) {
            return;
        }

        $classes = ArrayHelper::combine([
            'notice',
            'notice-'.ArrayHelper::get($data, 'type', 'info'),
        ], ArrayHelper::wrap(ArrayHelper::get($data, 'classes', [])));

        if (! empty($data['dismissible'])) {
            $classes[] = 'is-dismissible';
        } ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-message-id="<?php echo esc_attr(ArrayHelper::get($data, 'id', '')); ?>"><p><?php echo wp_kses_post($data['message']); ?></p></div>
        <?php
    }

    /**
     * Adds a notice for display.
     *
     * @param array $data
     */
    protected function registerNotice(array $data)
    {
        if (empty($data['id'])) {
            return;
        }

        $this->notices[$data['id']] = $data;
    }

    /**
     * Registers the notices that should be displayed.
     *
     * TODO: this method definitely needs to be broken up, and hopefully removed if we reactify these notices {@cwiseman 2021-05-24}
     *
     * @throws Exception
     */
    public function registerNotices()
    {
        // only an error notice if beginning onboarding fails
        if (ArrayHelper::get($_GET, 'onboardingError')) {
            $this->registerNotice([
                'dismissible' => true,
                'id'          => 'mwc-payments-godaddy-onboarding-error',
                'message'     => __('There was an error connecting to GoDaddy Payments. Please try again.', 'mwc-core'),
                'type'        => 'error',
            ]);

            return;
        }

        $status = Onboarding::getStatus();
        switch ($status) {
            case Onboarding::STATUS_CONNECTED:
                if ($this->isGatewayEnabled()) {
                    $message = sprintf(
                        __('%1$sGoDaddy Payments successfully enabled!%2$s GoDaddy Payments is now available to your customers at checkout.', 'mwc-core'),
                        '<strong>',
                        '</strong>'
                    );
                } else {
                    $message = sprintf(
                        __('%1$sGoDaddy Payments is now connected to your store!%2$s Enable the payment method to add it to your checkout. %3$sEnable GoDaddy Payments%4$s', 'mwc-core'),
                        '<strong>',
                        '</strong>',
                        '<a href="'.esc_url(OnboardingEventsProducer::getEnablePaymentMethodUrl()).'">',
                        '</a>'
                    );
                }
                $id = 'connected';
                $type = 'success';
                break;

            case Onboarding::STATUS_DISCONNECTED:
                $message = sprintf(
                    __('%1$sYour GoDaddy Payments account has been closed.%2$s The payment method has been disabled so it will not appear on your checkout. Please set up your account to resume processing payments.', 'mwc-core'),
                    '<strong>',
                    '</strong>'
                );
                $id = 'disconnected';
                $type = 'success';
                break;

            case Onboarding::STATUS_INCOMPLETE:
                $message = sprintf(
                    __('%1$sIt looks like you didn\'t finish your GoDaddy Payments application. You\'re just a few minutes from processing payments.%2$s %3$sResume%4$s', 'mwc-core'),
                    '<strong>',
                    '</strong>',
                    '<a href="'.esc_url(OnboardingEventsProducer::getOnboardingStartUrl('admin_notice_resume_link')).'">',
                    '</a>'
                );
                $id = 'incomplete';
                $type = 'success';
                break;

            case Onboarding::STATUS_SUSPENDED:
                $message = sprintf(
                    __('%1$sYour GoDaddy Payments account needs attention.%2$s The payment method has been disabled so it will not appear on your checkout. Please check your email for next steps.', 'mwc-core'),
                    '<strong>',
                    '</strong>'
                );
                $id = 'suspended';
                $type = 'warning';
                break;

            case Onboarding::STATUS_TERMINATED:
                $message = sprintf(
                    __('%1$sYour GoDaddy Payments account has been terminated.%2$s The payment method has been disabled so it will not appear on your checkout. Please check your email for more information.', 'mwc-core'),
                    '<strong>',
                    '</strong>'
                );
                $id = 'terminated';
                $type = 'error';
                break;
        }

        if (! empty($message) && ! empty($id) && ! empty($type)) {
            $this->registerNotice([
                'dismissible' => true,
                'id'          => "mwc-payments-godaddy-{$id}",
                'message'     => $message,
                'type'        => $type,
            ]);
        }

        if (
            ! Configuration::get('payments.poynt.onboarding.hasBankAccount', false)
            && Configuration::get('payments.poynt.onboarding.hasFirstPayment', false)
            && Configuration::get('payments.poynt.onboarding.depositsEnabled')
        ) {
            $this->registerNotice([
                'dismissible' => false,
                'id'          => 'mwc-payments-godaddy-link-bank-account',
                'message'     => sprintf(
                    __('Congratulations! To receive your payouts, please link your bank account to GoDaddy Payments. %1$sLink Bank Account%2$s', 'mwc-core'),
                    '<a href="'.esc_url(add_query_arg([
                        'businessId' => Poynt::getBusinessId(),
                        'openBankAccount' => 'true',
                    ], Poynt::getHubUrl())).'">',
                    '</a>'
                ),
                'type' => 'success',
            ]);
        }

        $this->registerPoyntPluginNotices();
        $this->registerApplePayNotices();

        $this->registerGdpRecommendationNotices();
        $this->registerGdpSipRecommendationNotices();

        // remaining notices only display if the gateway is connected & enabled
        if (! $this->isGatewayEnabled() || ! Onboarding::canEnablePaymentGateway(Onboarding::getStatus())) {
            return;
        }

        if (WooCommerceRepository::isWooCommerceActive() && 'US' !== WC()->countries->get_base_country()) {
            $this->registerNotice([
                'dismissible' => false,
                'id'          => 'mwc-payments-godaddy-non-us',
                'message'     => sprintf(
                    __('GoDaddy Payments is available for United States-based businesses. Please %1$supdate your Store Address%2$s if you are in the U.S.', 'mwc-core'),
                    '<a href="'.esc_url(admin_url('admin.php?page=wc-settings')).'">',
                    '</a>'
                ),
                'type' => 'warning',
            ]);
        }

        if ('USD' !== get_woocommerce_currency()) {
            $this->registerNotice([
                'dismissible' => false,
                'id'          => 'mwc-payments-godaddy-non-usd',
                'message'     => sprintf(
                    __('GoDaddy Payments requires U.S. dollar transactions. Please %1$schange your Currency%2$s in order to use the payment method.', 'mwc-core'),
                    '<a href="'.esc_url(admin_url('admin.php?page=wc-settings')).'">',
                    '</a>'
                ),
                'type' => 'warning',
            ]);
        }

        if (ManagedWooCommerceRepository::isStagingEnvironment()) {
            $this->registerNotice([
                'dismissible' => true,
                'id'          => 'mwc-payments-godaddy-staging',
                'message'     => __('WooCommerce charges or authorizations/captures as well as refunds and voids made in your Staging site will process normally in your GoDaddy Payments account.', 'mwc-core'),
                'type'        => 'warning',
            ]);
        }
    }

    /**
     * Determines whether the GoDaddy Payments gateway is enabled.
     *
     * We need to check the configuration value when the notices are being registered to make sure we catch the new settings values after the form in the settings page is saved.
     *
     * @return bool
     * @throws Exception
     */
    protected function isGatewayEnabled(): bool
    {
        // TODO: update the provider name if we rename poynt to godaddy-payments or something else {@wvega 2021-05-29}
        return Configuration::get('payments.poynt.enabled', false);
    }

    /**
     * Determines whether the GoDaddy Payments Sell in Person gateway is enabled.
     *
     * @return bool
     * @throws Exception
     */
    protected function isSiPGatewayEnabled(): bool
    {
        return (bool) Configuration::get('payments.godaddy-payments-payinperson.enabled', false);
    }

    /**
     * Determines whether the BOPIT feature is active.
     *
     * @return bool
     * @throws Exception
     */
    public static function isBOPITFeatureEnabled(): bool
    {
        return Configuration::get('features.bopit', false);
    }

    /**
     * Registers admin notices to display GoDaddy Payments Recommendation.
     *
     * @throws Exception
     */
    protected function registerGdpRecommendationNotices()
    {
        if (! $this->shouldRegisterGdpRecommendationNotices()) {
            return;
        }

        $this->registerNotice([
            'dismissible' => true,
            'classes'     => 'mwc-godaddy-payments-recommendation',
            'id'          => 'mwc-godaddy-payments-recommendation',
            'message'     => sprintf(
                '<img src="%1$s" alt="'.esc_attr__('Provided by GoDaddy', 'mwc-core').'"/>
                <h3>'.esc_html__('GoDaddy Payments', 'mwc-core').'</h3>
                <p>'.esc_html__('Sell online and in person with GoDaddy Payments. Sync local pickup and delivery orders right to your Smart Terminal, then get paid fast with next-day deposits.', 'mwc-core').'</p>
                <a href="%2$s" class="mwc-button">'.esc_html__('Get Started', 'mwc-core').'</a>',
                esc_url(WordPressRepository::getAssetsUrl('images/branding/gd-icon.svg')),
                esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&gdpsetup=true'))
            ),
            'type'        => 'info',
        ]);
    }

    /**
     * Registers admin notices to display GoDaddy Sell in Person Recommendation.
     *
     * @throws Exception
     */
    protected function registerGdpSipRecommendationNotices()
    {
        if (! $this->shouldRegisterGdpSipRecommendationNotices()) {
            return;
        }

        $this->registerNotice([
            'dismissible' => true,
            'classes'     => 'mwc-godaddy-payments-recommendation',
            'id'          => 'mwc-godaddy-payments-sip-recommendation',
            'message'     => sprintf(
                '<img src="%1$s" alt="'.esc_attr__('Provided by GoDaddy', 'mwc-core').'"/>
                <h3>'.esc_html__('GoDaddy Selling in Person', 'mwc-core').'</h3>
                <p>'.esc_html__('Use GoDaddy Payments Selling in Person to sync local pickup and delivery orders to your Smart Terminal. Sell anything, anywhere and get paid fast with next-day deposits.', 'mwc-core').'</p>
                <a class="mwc-button" href="%2$s">'.esc_html__('Get Started', 'mwc-core').'</a>',
                esc_url(WordPressRepository::getAssetsUrl('images/branding/gd-icon.svg')),
                esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=godaddy-payments-payinperson'))
            ),
            'type'        => 'info',
        ]);
    }

    /**
     * Determines whether GoDaddy Payments Recommendation Notice should be registered.
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldRegisterGdpRecommendationNotices(): bool
    {
        if (! self::isBOPITFeatureEnabled() || Onboarding::getStatus() !== '' || ! WordPressRepository::isCurrentPage('wc-settings')) {
            return false;
        }

        return ArrayHelper::contains(static::GDP_RECOMMENDATION_SECTIONS, ArrayHelper::get($_GET, 'section'))
            || (ArrayHelper::contains(static::GDP_RECOMMENDATION_TABS, ArrayHelper::get($_GET, 'tab')) && ($this->isLocalPickupEnabled() || $this->isLocalDeliveryEnabled()));
    }

    /**
     * Determines whether GoDaddy Payments Recommendation Notice should be registered for the Sell in Person gateway.
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldRegisterGdpSipRecommendationNotices(): bool
    {
        if (
            ! self::isBOPITFeatureEnabled()
            || $this->isSiPGatewayEnabled()
            || ! Onboarding::canEnablePaymentGateway(Onboarding::getStatus())
            || ! WordPressRepository::isCurrentPage('wc-settings')
        ) {
            return false;
        }

        return ArrayHelper::contains(static::GDP_SIP_RECOMMENDATION_SECTIONS, ArrayHelper::get($_GET, 'section'))
            || (ArrayHelper::contains(static::GDP_SIP_RECOMMENDATION_TABS, ArrayHelper::get($_GET, 'tab')) && ($this->isLocalPickupEnabled() || $this->isLocalDeliveryEnabled()));
    }

    /**
     * Registers admin notices that should be rendered if the Poynt plugin is active.
     */
    protected function registerPoyntPluginNotices()
    {
        if (! $this->isPluginActive(static::GODADDY_PAYMENTS_PLUGIN_PATH)) {
            return;
        }

        $this->registerNotice([
            'dismissible' => true,
            'id'          => 'mwc-payments-godaddy-payments-already-included',
            'message'     => sprintf(
                __('GoDaddy Payments (Poynt) is included for Managed WordPress customers without a separate plugin! Go to %1$sPayments settings%2$s to enable it.', 'mwc-core'),
                '<a href="'.esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')).'">',
                '</a>'
            ),
            'type'        => 'info',
        ]);
    }

    /**
     * Registers Apple Pay notices.
     *
     * @throws Exception
     */
    protected function registerApplePayNotices()
    {
        if (true !== Configuration::get('payments.applePay.enabled', false) || ! ApplePayGateway::isActive()) {
            return;
        }

        $page = ArrayHelper::get($_GET, 'page');
        $section = ArrayHelper::get($_GET, 'section');
        $hasEnabledPages = ! empty(Configuration::get('payments.applePay.enabledPages'));

        // only display this notice on the Apple Pay settings page
        if ($hasEnabledPages || 'wc-settings' !== $page || 'godaddy-payments-apple-pay' !== $section) {
            return;
        }

        $this->registerNotice([
            'dismissible' => false,
            'id'          => 'mwc-payments-godaddy-payments-apple-pay-no-enabled-pages',
            'message'     =>  __('Please select the pages where Apple Pay should show.', 'mwc-core'),
            'type'        => 'error',
        ]);
    }

    /**
     * Determines whether the zones have Local Pickup Method enabled.
     *
     * @return bool
     * @throws Exception
     */
    protected function isLocalPickupEnabled() : bool
    {
        $shippingZones = WooCommerceRepository::isWooCommerceActive() ? WC_Shipping_Zones::get_zones() : [];

        foreach (ArrayHelper::wrap($shippingZones) as $zone) {
            $localPickupShippingMethods = ArrayHelper::where(ArrayHelper::get($zone, 'shipping_methods', []), static function ($method) {
                return static::WC_LOCAL_PICKUP === $method->id;
            });

            return ! empty($localPickupShippingMethods);
        }

        return false;
    }

    /**
     * Determines whether the zones have Local Delivery Method enabled.
     *
     * @return bool
     * @throws Exception
     */
    protected function isLocalDeliveryEnabled() : bool
    {
        $shippingZones = WooCommerceRepository::isWooCommerceActive() ? WC_Shipping_Zones::get_zones() : [];

        foreach (ArrayHelper::wrap($shippingZones) as $zone) {
            $localDeliveryShippingMethods = ArrayHelper::where(ArrayHelper::get($zone, 'shipping_methods', []), static function ($method) {
                return 'mwc_local_delivery' === $method->id;
            });

            return ! empty($localDeliveryShippingMethods);
        }

        return false;
    }

    /**
     * Determines whether the given plugin is active.
     *
     * TODO: add this method to the WordPressRepository or make it possible to create PluginExtension objects from installed (non-managed) plugins {@wvega 2021-06-03}
     *
     * @param string path to the plugin file relative to the plugins directory
     * @return bool
     */
    protected function isPluginActive(string $path): bool
    {
        return is_plugin_active($path);
    }
}
