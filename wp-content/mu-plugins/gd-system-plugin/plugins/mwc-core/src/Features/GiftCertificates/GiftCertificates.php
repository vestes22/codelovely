<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Events\FeatureEnabledEvent;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Integrations\PDFProductVouchersIntegration;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components\GoDaddyBranding;
use GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Loader;

/**
 * The Gift Certificates feature loader.
 */
class GiftCertificates implements ConditionalComponentContract
{
    use HasComponentsTrait;

    /** @var string name of the option used to determine whether we should broadcast the feature enabled event */
    private $featureEnabledEventOptionName = 'mwc_broadcast_gift_certificates_feature_enabled_event';

    /** @var string[] component classes to load */
    protected $componentClasses = [
        PDFProductVouchersIntegration::class,
    ];

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function load()
    {
        $this->loadCommunityPlugin();
        $this->loadComponents();
        $this->registerHooks();
    }

    /**
     * Loads the community plugin.
     */
    protected function loadCommunityPlugin()
    {
        $rootVendorPath = StringHelper::trailingSlash(StringHelper::before(__DIR__, 'src').'vendor');

        // Load plugin class file
        require_once $rootVendorPath.'godaddy/mwc-gift-certificates/woocommerce-pdf-product-vouchers.php';

        // load SV Framework from root vendor folder first
        require_once $rootVendorPath.'skyverge/wc-plugin-framework/woocommerce/class-sv-wc-plugin.php';

        MWC_Gift_Certificates_Loader::instance()->init_plugin();
    }

    /**
     * Register the hooks for the Gift Certificates feature.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_init')
            ->setCondition([$this, 'shouldBroadcastFeatureEnabledEvent'])
            ->setHandler([$this, 'broadcastFeatureEnabledEvent'])
            ->execute();

        Register::action()
            ->setGroup('admin_head')
            ->setHandler([$this, 'registerGoDaddyBrandingHooks'])
            ->execute();
    }

    /**
     * Registers hooks needed for adding GoDaddy branding.
     *
     * @internal
     *
     * @throws Exception
     */
    public function registerGoDaddyBrandingHooks()
    {
        Register::action()
            ->setGroup('admin_footer')
            ->setHandler([$this, 'addGoDaddyBrandingStyles'])
            ->setCondition([$this, 'shouldAddGoDaddyBranding'])
            ->execute();

        Register::action()
            ->setGroup('admin_footer')
            ->setHandler([GoDaddyBranding::getInstance(), 'render'])
            ->setCondition([$this, 'shouldAddGoDaddyBranding'])
            ->execute();
    }

    /**
     * Adds the style tag used by the GoDaddy branding.
     *
     * @internal
     */
    public function addGoDaddyBrandingStyles()
    {
        ob_start(); ?>
        <style>
            .mwc-gd-branding {
                position: absolute;
                bottom: 18px;
                left: 180px;
            }

            #wpfooter {
                display: none;
            }

            @media screen and (max-width: 960px) {
                .mwc-gd-branding {
                    left: 55px;
                }
            }

            @media screen and (max-width: 782px) {
                .mwc-gd-branding {
                    position: relative;
                    left: 12px;
                    bottom: 8px;
                }
            }
        </style>
        <?php

        (GoDaddyBranding::getInstance())->addStyle(ob_get_clean());
    }

    /**
     * Checks if it should add GoDaddy branding to feature pages.
     *
     * @internal
     *
     * @throws Exception
     * @return bool
     */
    public function shouldAddGoDaddyBranding() : bool
    {
        if (ManagedWooCommerceRepository::isReseller()) {
            return false;
        }

        $screen = WordPressRepository::getCurrentScreen();

        return $screen && in_array($screen->getPageId(), [
            'wc_voucher_template_list',
            'wc_voucher_list',
            'edit_wc_voucher',
            'add_wc_voucher',
            'admin_page_wc-pdf-product-vouchers-redeem-voucher',
        ], true);
    }

    /**
     * Broadcasts a FeatureEnabledEvent when the Gift Certificates feature is enabled for the first time.
     *
     * @internal
     *
     * @throws Exception
     */
    public function broadcastFeatureEnabledEvent()
    {
        Events::broadcast(new FeatureEnabledEvent('gift_certificates'));

        update_option($this->featureEnabledEventOptionName, 'no');
    }

    /**
     * Determines whether it should broadcast a FeatureEnabledEvent for the Gift Certificates feature.
     *
     * @internal
     *
     * @return bool
     */
    public function shouldBroadcastFeatureEnabledEvent() : bool
    {
        // try to limit processing to document requests initiated by a merchant on the admin dashboard
        if (WordPressRepository::isAjax() || ! current_user_can('manage_woocommerce')) {
            return false;
        }

        if (false !== get_option($this->featureEnabledEventOptionName, false)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function shouldLoad() : bool
    {
        return Configuration::get('features.gift_certificates.enabled')
            && ManagedWooCommerceRepository::hasEcommercePlan()
            && ! ManagedWooCommerceRepository::isReseller();
    }
}
