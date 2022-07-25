<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Integrations;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionDeactivationFailedException;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Core\Events\PluginDeactivatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\WooCommerce\Admin\EmailSettings;

/**
 * Integration class for the Email Customizer plugin.
 */
class EmailCustomizerIntegration implements ComponentContract
{
    /** @var PluginExtension|null plugin extension instance for the Email Customizer plugin */
    protected $plugin;

    /** @var bool */
    protected $showRedirectNotice = false;

    /**
     * Loads the EmailCustomizerIntegration component.
     *
     * @throws Exception
     */
    public function load()
    {
        // no need for executing the method on hook action as Core already loads on the "plugins_loaded" hook
        $this->maybeDeactivateEmailCustomizerPlugin();

        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'renderRedirectNotice'])
            ->setCondition([$this, 'shouldRenderRedirectNotice'])
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
     * @throws Exception
     */
    public function removePluginUpdateNotice()
    {
        if ($plugin = $this->getPlugin()) {
            remove_action('after_plugin_row_'.$plugin->getBasename(), 'wp_plugin_update_row');
        }
    }

    /**
     * Renders redirect notice to the native feature.
     *
     * @internal
     * @throws Exception
     */
    public function renderRedirectNotice()
    {
        $this->getWooCommerceEmailSettings()->renderRedirectNotice();
    }

    /**
     *Gets WooCommerce Email Settings page component instance.
     *
     * @return EmailSettings
     */
    protected function getWooCommerceEmailSettings() : EmailSettings
    {
        return new EmailSettings();
    }

    /**
     * Determines whether to show the native feature redirect notice or not.
     *
     * @return bool
     */
    public function shouldRenderRedirectNotice() : bool
    {
        return true === $this->showRedirectNotice;
    }

    /**
     * Deactivates the Email Customizer plugin if certain conditions are met.
     *
     * @throws Exception
     */
    protected function maybeDeactivateEmailCustomizerPlugin()
    {
        if ($this->isEmailCustomizerPluginActive() && $this->isEmailNotificationsActive()) {
            try {
                $this->getPlugin()->deactivate();
                $this->replaceActivatedNoticeWithRedirectNotice();
            } catch (ExtensionDeactivationFailedException $exception) {
                // return early and let the plugin try again on the next request
                return;
            }

            Events::broadcast(new PluginDeactivatedEvent($this->getPlugin()));
        }
    }

    /**
     * Replaces the plugin activated admin notice with another to redirect users to the native feature.
     */
    protected function replaceActivatedNoticeWithRedirectNotice()
    {
        // prevent the active notice from showing up
        ArrayHelper::set($_GET, 'activate', null);

        // flag to show the redirect notice
        $this->showRedirectNotice = true;
    }

    /**
     * Determines whether the Email Notifications feature is active.
     *
     * @return bool
     * @throws Exception
     */
    protected function isEmailNotificationsActive() : bool
    {
        return EmailNotifications::isActive();
    }

    /**
     * Determines whether the Email Customizer plugin is currently installed and active.
     *
     * @return bool
     * @throws Exception
     */
    protected function isEmailCustomizerPluginActive() : bool
    {
        return $this->getPlugin() && $this->getPlugin()->isActive();
    }

    /**
     * Looks for the installed managed Email Customizer plugin and memoizes the result.
     * If plugin is not found, returns null.
     *
     * @return PluginExtension|null
     * @throws Exception
     */
    protected function getPlugin()
    {
        if ($this->plugin) {
            return $this->plugin;
        }

        $this->plugin = ManagedExtensionsRepository::getInstalledManagedPlugin(
            'woocommerce-email-customizer/woocommerce-email-customizer.php'
        );

        return $this->plugin;
    }
}
