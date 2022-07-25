<?php

namespace GoDaddy\WordPress\MWC\Core\Client;

use BadMethodCallException;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Providers\Contracts\ProviderContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Admin\Views\Components\PlatformContainerElement;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\Notices;
use GoDaddy\WordPress\MWC\Dashboard\Menu\GetHelpMenu;
use GoDaddy\WordPress\MWC\Shipping\Shipping;

/**
 * MWC Client class.
 *
 * @since 2.10.0
 */
class Client
{
    /** @var string the app source, normally a URL */
    protected $appSource;

    /** @var string the identifier of the application */
    protected $appHandle;

    /**
     * MWC Client constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->appHandle = 'mwcClient';
        $this->appSource = Configuration::get('mwc.client.index.url');

        $this->registerHooks();
    }

    /**
     * Registers the client's hook handlers.
     *
     * @since 2.10.0
     *
     * @return Client
     * @throws Exception
     */
    protected function registerHooks() : Client
    {
        Register::action()
            ->setGroup('admin_body_class')
            ->setHandler([$this, 'addAdminBodyClasses'])
            ->execute();

        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueAssets'])
            ->execute();

        Register::action()
            ->setGroup('admin_print_styles')
            ->setHandler([$this, 'enqueueMessagesContainerStyles'])
            ->execute();

        Register::action()
            ->setGroup('all_admin_notices')
            ->setHandler([$this, 'renderMessagesContainer'])
            ->execute();

        Register::action()
            ->setGroup('admin_footer')
            ->setHandler([$this, 'renderPlatformAppContainer'])
            ->execute();

        return $this;
    }

    /**
     * Adds utility classes to the body element in the admin dashboard.
     *
     * @internal
     *
     * @since 2.1.4
     *
     * @param string $classes space separated list of classes for the body element in the admin dashboard
     */
    public function addAdminBodyClasses($classes)
    {
        if (! $version = $this->getWooCommerceVersion()) {
            return;
        }

        if (version_compare($version, '5.2.0', '>=')) {
            $classes .= ' mwc-wc-version-gte-5-2';
        }

        return $classes;
    }

    /**
     * Gets the currently active version of WooCommerce or null if the plugin is not active.
     *
     * We can't use woocommerce.version configuration value because that's currently always set to null.
     *
     * @since 2.1.4
     *
     * @return string|null
     */
    protected function getWooCommerceVersion()
    {
        return defined('WC_VERSION') ? constant('WC_VERSION') : null;
    }

    /**
     * Render the styles for the container div.
     *
     * @since 2.10.0
     *
     * @return void
     * @throws Exception
     */
    public function enqueueMessagesContainerStyles()
    {
        Enqueue::style()
            ->setHandle("{$this->appHandle}-main-styles")
            ->setSource(StringHelper::trailingSlash(Configuration::get('mwc.assets.styles')).'main.css')
            ->execute();

        Enqueue::style()
            ->setHandle("{$this->appHandle}-messages-styles")
            ->setSource(WordPressRepository::getAssetsUrl('css/mwc-messages-container.css'))
            ->execute();
    }

    /**
     * Render the styles for the container div.
     *
     * @since 2.10.0
     *
     * @return void
     * @throws Exception
     */
    public function renderMessagesContainer()
    {
        ?>
        <div id="mwc-messages-container" class="mwc-messages-container"></div>
        <?php
    }

    /**
     * Renders the container div for the platform app.
     *
     * @since 2.10.0
     *
     * @return void
     * @throws Exception
     */
    public function renderPlatformAppContainer()
    {
        PlatformContainerElement::renderIfNotRendered();
    }

    /**
     * Enqueues/loads registered assets.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function enqueueAssets()
    {
        Enqueue::script()
               ->setHandle("{$this->appHandle}-runtime")
               ->setSource(Configuration::get('mwc.client.runtime.url'))
               ->setDeferred(true)
               ->execute();

        Enqueue::script()
               ->setHandle("{$this->appHandle}-vendors")
               ->setSource(Configuration::get('mwc.client.vendors.url'))
               ->setDeferred(true)
               ->execute();

        $this->enqueueApp();
        $this->enqueueNoticesAssets();
    }

    /**
     * Enqueues the single page application script.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    protected function enqueueApp()
    {
        $script = Enqueue::script()
                         ->setHandle($this->appHandle)
                         ->setSource($this->appSource)
                         ->setDeferred(true);

        $inlineScriptVariables = $this->getInlineScriptVariables();

        if (! empty($inlineScriptVariables)) {
            $script->attachInlineScriptObject($this->appHandle)
                   ->attachInlineScriptVariables($inlineScriptVariables);
        }

        $script->execute();
    }

    /**
     * Gets inline script variables.
     *
     * @since 2.10.0
     *
     * @return array
     * @throws Exception
     */
    protected function getInlineScriptVariables() : array
    {
        return array_merge(
            $this->getFeatureFlagsContextVariables(),
            $this->getClientContextVariables(),
            $this->getPageContextVariables(),
            $this->getPermissionsContextVariables(),
            $this->getShippingContextVariables()
        );
    }

    /**
     * Gets inline script variables for feature flags.
     *
     * @return array
     */
    protected function getFeatureFlagsContextVariables() : array
    {
        return ['featureFlags' => $this->getFeatures()->featureFlags()];
    }

    /**
     * Get an instance of the Features class.
     *
     * @return Features
     */
    protected function getFeatures() : Features
    {
        return new Features();
    }

    /**
     * Gets the default inline script variables for the client.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getClientContextVariables() : array
    {
        return [
            'root'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ];
    }

    /**
     * Gets inline script variables that describe the current page.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getPageContextVariables() : array
    {
        $currentScreen = WordPressRepository::getCurrentScreen();

        return $currentScreen ? $currentScreen->toArray() : [];
    }

    /**
     * Gets the default permissions variables for the client.
     *
     * @since 2.10.0
     *
     * @return array
     * @throws Exception
     */
    protected function getPermissionsContextVariables() : array
    {
        return [
            'api'            => current_user_can('edit_posts'),
            'installPlugins' => current_user_can('install_plugins') && current_user_can('activate_plugins'),
            'getHelp'        => current_user_can(GetHelpMenu::CAPABILITY) && GetHelpMenu::shouldLoadConditionalFeature(),
        ];
    }

    /**
     * Gets inline script variables that describe the available shipping providers.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getShippingContextVariables() : array
    {
        return [
            'shipping' => [
                'providers' => $this->getShippingProvidersData(),
            ],
        ];
    }

    /**
     * Gets data for the registered shipping providers.
     *
     * @since 2.10.0
     *
     * @return array
     */
    protected function getShippingProvidersData() : array
    {
        $providers = array_values(array_map(function (ProviderContract $provider) {
            return [
                'label' => $provider->getLabel(),
                'name' => $provider->getName(),
                'trackingUrl' => $this->getTrackingUrlTemplate($provider),
            ];
        }, Shipping::getInstance()->getProviders()));

        $providers[] = [
            'label' => __('Other', 'mwc-core'),
            'name'  => 'other',
            'trackingUrl' => null,
        ];

        return $providers;
    }

    /**
     * Returns the tracking URL template for the given provider, if any.
     *
     * @since 2.10.0
     *
     * @param ProviderContract $provider
     *
     * @return string | null
     */
    protected function getTrackingUrlTemplate(ProviderContract $provider)
    {
        try {
            $tracking = $provider->tracking();
        } catch (BadMethodCallException $e) {
            return null;
        }

        if (! is_callable([$tracking, 'getTrackingUrlTemplate'])) {
            return null;
        }

        return $tracking->getTrackingUrlTemplate();
    }

    /**
     * Enqueues the notices script.
     */
    protected function enqueueNoticesAssets()
    {
        Enqueue::script()
            ->setHandle("{$this->appHandle}-notices")
            ->setSource(WordPressRepository::getAssetsUrl('js/notices.js'))
            ->setDeferred(true)
            ->attachInlineScriptObject('MWCNotices')
            ->attachInlineScriptVariables([
                'dismissNoticeAction' => Notices::ACTION_DISMISS_NOTICE,
            ])
            ->execute();
    }
}
