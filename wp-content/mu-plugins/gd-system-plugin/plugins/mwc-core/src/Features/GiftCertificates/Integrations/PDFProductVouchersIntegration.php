<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\Integrations;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionDeactivationFailedException;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;

class PDFProductVouchersIntegration implements ComponentContract
{
    /** @var string the plugin name */
    protected static $communityPluginName = 'woocommerce-pdf-product-vouchers/woocommerce-pdf-product-vouchers.php';

    /** @var string the community plugin slug */
    protected static $communityPluginSlug = 'woocommerce-pdf-product-vouchers';

    /** @var string the native feature available flag option name */
    protected static $featureAvailableNoticeName = 'mwc_show_gift_certificates_native_feature_available_notice';

    /** @var string the plugin reactivation flag option name */
    protected static $reactivationNoticeName = 'mwc_show_pdf_product_vouchers_plugin_reactivated_notice';

    /** @var PluginExtension|null plugin extension instance */
    protected $plugin;

    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->registerHooks();
    }

    /**
     * Registers integration hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        if (WordPressRepository::isAjax()) {
            return;
        }

        Register::action()
            ->setGroup('init')
            ->setHandler([$this, 'maybeDeactivatePdfProductVouchersPlugin'])
            ->execute();

        Register::action()
            ->setGroup('load-plugins.php')
            ->setHandler([$this, 'removePluginUpdateNotice'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }

    /**
     * Removes the WP action that displays the plugin update notice below each plugin on the Plugins page.
     *
     * @internal
     *
     * @throws Exception
     */
    public function removePluginUpdateNotice()
    {
        remove_action('after_plugin_row_'.static::$communityPluginName, 'wp_plugin_update_row');
    }

    /**
     * Deactivates the PDF Product Vouchers plugin.
     *
     * @throws Exception
     */
    public function deactivatePdfProductVouchersPlugin()
    {
        try {
            if ($plugin = $this->getPlugin()) {
                $plugin->deactivate();
            }
        } catch (ExtensionDeactivationFailedException $e) {
            return;
        }
    }

    /**
     * Looks for the installed managed PDF Product Vouchers plugin and memoizes the result.
     * If plugin is not found, returns null.
     *
     * @return PluginExtension|null
     * @throws Exception
     */
    protected function getPlugin()
    {
        if (! $this->plugin) {
            $this->plugin = ManagedExtensionsRepository::getInstalledManagedPlugin(
                static::$communityPluginName
            );
        }

        return $this->plugin;
    }

    /**
     * Determines whether the Shipment Tracking plugin is currently installed and active.
     *
     * @throws Exception
     */
    protected function isPdfProductVouchersPluginActive() : bool
    {
        return $this->getPlugin() && $this->getPlugin()->isActive();
    }

    /**
     * Deactivates the PDF Product Vouchers plugin if certain conditions are met.
     *
     * @throws Exception
     */
    public function maybeDeactivatePdfProductVouchersPlugin()
    {
        if ($this->isPdfProductVouchersPluginActive()) {
            $this->updatePluginNoticeOptions();
            $this->deactivatePdfProductVouchersPlugin();
        }
    }

    /**
     * Updates the plugin reactivation notice option value.
     */
    protected function updatePluginNoticeOptions()
    {
        // unset GET param so that the "Plugin activated." notice is not displayed
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        if (false === get_option(static::$featureAvailableNoticeName, false)) {
            // set the option to determine whether we should show a notice to merchants that had the community plugin when the native feature became available
            update_option(static::$featureAvailableNoticeName, 'yes');
        }

        if (false === get_option(static::$reactivationNoticeName, false)) {
            // the community plugin is being deactivated automatically for the 1st time, so this is not a reactivation attempt
            add_option(static::$reactivationNoticeName, 'no', '', 'no');
        } else {
            // the community plugin was already deactivated automatically before, this is a reactivation attempt
            update_option(static::$reactivationNoticeName, 'yes');
            // we want to display the notice again even it was previously dismissed
            $this->getPackageInstance()->get_admin_notice_handler()->undismiss_notice($this->getPackageInstance()->get_id().'_plugin_reactivated');
        }
    }

    /**
     * Gets instance of package's plugin.
     *
     * @return MWC_Gift_Certificates
     */
    protected function getPackageInstance() : MWC_Gift_Certificates
    {
        return wc_pdf_product_vouchers();
    }
}
