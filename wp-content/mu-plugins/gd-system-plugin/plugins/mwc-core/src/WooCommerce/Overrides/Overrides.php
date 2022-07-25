<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Overrides;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

class Overrides implements ConditionalComponentContract
{
    use HasComponentsTrait;

    /** @var array alphabetically ordered list of components to load */
    protected $componentClasses = [
        RedirectToEmailSettings::class,
    ];

    /**
     * Initializes the component.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->loadComponents();
        $this->registerActions();
        $this->registerFilters();
    }

    /**
     * Registers actions.
     *
     * @throws Exception
     */
    private function registerActions()
    {
        Register::action()
            ->setGroup('plugins_loaded')
            ->setHandler([$this, 'setDefaults'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(0)
            ->execute();

        // may disable marketplace suggestions
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'maybeDisableMarketplaceSuggestions'])
            ->setArgumentsCount(0)
            ->execute();
    }

    /**
     * Registers filters.
     *
     * @throws Exception
     */
    private function registerFilters()
    {
        Register::filter()
            ->setGroup('woocommerce_show_admin_notice')
            ->setHandler([$this, 'suppressNotices'])
            ->setPriority(10)
            ->setArgumentsCount(2)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_helper_suppress_connect_notice')
            ->setHandler([$this, 'suppressConnectNotice'])
            ->setPriority(PHP_INT_MAX)
            ->setArgumentsCount(1)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_hidden_order_itemmeta')
            ->setHandler([$this, 'hideOrderItemMeta'])
            ->execute();

        Register::filter()
            ->setGroup('wc_pdf_product_vouchers_admin_hide_low_memory_notice')
            ->setCondition(function () {
                return ManagedWooCommerceRepository::isManagedWordPress();
            })
            ->setHandler([$this, 'hidePdfProductVouchersLowMemoryNotice'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        Register::filter()
            ->setGroup('wc_pdf_product_vouchers_admin_hide_sucuri_notice')
            ->setCondition(function () {
                return ManagedWooCommerceRepository::isManagedWordPress();
            })
            ->setHandler([$this, 'hidePdfProductVouchersSucuriNotice'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        // add the authentication headers necessary for getting packages from the Extensions API
        Register::filter()
            ->setGroup('http_request_args')
            ->setCondition(function () {
                return ManagedWooCommerceRepository::isManagedWordPress();
            })
            ->setHandler([$this, 'addExtensionsApiAuthenticationHeaders'])
            ->setPriority(10)
            ->setArgumentsCount(2)
            ->execute();

        // ensure checkout is always HTTPS for temp sites
        Register::filter()
            ->setGroup('pre_option_woocommerce_force_ssl_checkout')
            ->setCondition(function () {
                return ManagedWooCommerceRepository::hasEcommercePlan();
            })
            ->setHandler([$this, 'maybeSetForceSsl'])
            ->setPriority(10)
            ->setArgumentsCount(1)
            ->execute();

        Register::filter()
            ->setGroup('pre_option_woocommerce_task_list_hidden')
            ->setHandler([$this, 'hideWooCommerceTaskList'])
            ->setCondition([ManagedWooCommerceRepository::class, 'hasEcommercePlan'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Returns a flag to hide WooCommerce setup widget in the dashboard.
     *
     * @internal
     *
     * @return string
     */
    public function hideWooCommerceTaskList() : string
    {
        return 'yes';
    }

    /**
     * Set option defaults for a better experience on the MWP eCommerce plan.
     *
     * @action plugins_loaded - PHP_INT_MAX
     */
    public function setDefaults()
    {
        if (! ManagedWooCommerceRepository::hasEcommercePlan()) {
            return;
        }

        if (class_exists('WC_Admin_Notices') && ! ManagedWooCommerceRepository::hasCompletedWPNuxOnboarding()) {
            \WC_Admin_Notices::remove_notice('install', true);
        }

        if ('no' !== get_option('woocommerce_onboarding_opt_in')) {
            update_option('woocommerce_onboarding_opt_in', 'no');
        }

        if ('yes' !== get_option('woocommerce_task_list_hidden')) {
            update_option('woocommerce_task_list_hidden', 'yes');
        }

        $onboarding_profile = (array) get_option('woocommerce_onboarding_profile', []);

        if (empty($onboarding_profile['completed'])) {
            update_option('woocommerce_onboarding_profile', array_merge($onboarding_profile, ['completed' => true]));
        }
    }

    /**
     * Adds the authentication headers necessary for getting packages from the Extensions API.
     *
     * @internal
     *
     * @param mixed $requestArgs request args
     * @param string $url request URL
     *
     * @return mixed
     * @throws Exception
     */
    public function addExtensionsApiAuthenticationHeaders($requestArgs, string $url)
    {
        // target admin extensions API requests only
        if ((is_admin() || WordPressRepository::isApiRequest()) && StringHelper::contains($url, Configuration::get('mwc.extensions.api.url'))) {
            ArrayHelper::set($requestArgs, 'headers.X-Site-Token', Configuration::get('godaddy.site.token', 'empty'));
            ArrayHelper::set($requestArgs, 'headers.X-Account-UID', Configuration::get('godaddy.account.uid', ''));
        }

        return $requestArgs;
    }

    /**
     * Ensures checkout is always HTTPS for temp sites.
     *
     * @internal
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function maybeSetForceSsl($value)
    {
        if (ManagedWooCommerceRepository::isTemporaryDomain()) {
            return 'yes';
        }

        return $value;
    }

    /**
     * Callback for the woocommerce_helper_suppress_connect_notice filter.
     *
     * @internal
     *
     * @return bool
     */
    public function suppressConnectNotice()
    {
        return ManagedWooCommerceRepository::hasEcommercePlan();
    }

    /**
     * Adds meta keys to the list of order item meta keys that should be hidden.
     *
     * @internal
     *
     * @param array $keys hidden order item meta keys
     *
     * @return array
     */
    public function hideOrderItemMeta($keys)
    {
        return ArrayHelper::combine(ArrayHelper::wrap($keys), Configuration::get('woocommerce.hiddenOrderItemMeta', []));
    }

    /**
     * Callback for the wc_pdf_product_vouchers_admin_hide_low_memory_notice filter.
     *
     * @internal
     *
     * @return bool
     */
    public function hidePdfProductVouchersLowMemoryNotice()
    {
        return true;
    }

    /**
     * Callback for the wc_pdf_product_vouchers_admin_hide_sucuri_notice filter.
     *
     * @internal
     *
     * @return bool
     */
    public function hidePdfProductVouchersSucuriNotice()
    {
        return true;
    }

    /**
     * Suppress WooCommerce admin notices.
     *
     * @filter woocommerce_show_admin_notice - 10
     *
     * @param  bool   $bool   Boolean value to show/suppress the notice.
     * @param  string $notice The notice name being displayed.
     *
     * @return bool True to show the notice, false to suppress it.
     */
    public function suppressNotices($bool, $notice)
    {

        // Suppress the SSL notice when hosted on MWP on a temp domain.
        if ('no_secure_connection' === $notice && ManagedWooCommerceRepository::isTemporaryDomain()) {
            return false;
        }

        // Suppress the "Install WooCommerce Admin" notice when the Setup Wizard notice is visible.
        if ('wc_admin' === $notice && in_array('install', (array) get_option('woocommerce_admin_notices', []), true)) {
            return false;
        }

        return $bool;
    }

    /**
     * Callback to determine whether to disable the marketplace suggestions.
     *
     * @internal
     *
     * @throws Exception
     */
    public function maybeDisableMarketplaceSuggestions()
    {
        if ($this->shouldDisableMarketplaceSuggestions()) {
            $this->disableMarketplaceSuggestions();
        }
    }

    /**
     * Disables marketplace suggestions.
     *
     * @throws Exception
     */
    protected function disableMarketplaceSuggestions()
    {
        update_option('woocommerce_show_marketplace_suggestions', 'no');

        update_option('gd_mwc_disable_woocommerce_marketplace_suggestions', 'no');

        Configuration::set('woocommerce.flags.disableMarketplaceSuggestions', 'no');
    }

    /**
     * Determines whether the marketplace suggestions should be disabled.
     *
     * @return bool
     *
     * @throws Exception
     */
    private function shouldDisableMarketplaceSuggestions() : bool
    {
        // 1617235200 is the timestamp for April 1, 2021
        $isSiteCreatedAfterApril2021 = Configuration::get('godaddy.site.created', 0) >= 1617235200;

        return $isSiteCreatedAfterApril2021 && 'yes' === Configuration::get('woocommerce.flags.disableMarketplaceSuggestions');
    }

    /**
     * Determines whether to load the feature.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoad(): bool
    {
        return WooCommerceRepository::isWooCommerceActive() && ManagedWooCommerceRepository::hasEcommercePlan();
    }
}
