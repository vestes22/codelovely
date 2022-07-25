<?php

namespace GoDaddy\WordPress\MWC\Core;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Plugin\BasePlatformPlugin;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Core\Admin\Notices;
use GoDaddy\WordPress\MWC\Core\Client\Client;
use GoDaddy\WordPress\MWC\Core\Events\Producers;
use GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\CostOfGoods;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\GiftCertificates\GiftCertificates;
use GoDaddy\WordPress\MWC\Core\Features\GoogleAnalytics\GoogleAnalytics;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Dashboard as OnboardingDashboard;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Onboarding;
use GoDaddy\WordPress\MWC\Core\Features\SequentialOrderNumbers\SequentialOrderNumbers;
use GoDaddy\WordPress\MWC\Core\Features\UrlCoupons\UrlCoupons;
use GoDaddy\WordPress\MWC\Core\Pages\Plugins\IncludedWooCommerceExtensionsTab;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\OrderSynchronization;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\ViewOrderPage;
use GoDaddy\WordPress\MWC\Core\WooCommerce\ExtensionsTab;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\WooCommerceInterceptor;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Overrides\Overrides;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\CorePaymentGateways;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\CoreShippingMethods;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\LocalPickup;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\RemoveShipmentTrackingFromManagedWordPressSites;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\ShipmentTracking;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Updates;
use GoDaddy\WordPress\MWC\Dashboard\Dashboard;

/**
 * MWC Core package class.
 *
 * @since 2.10.0
 */
final class Package extends BasePlatformPlugin
{
    use IsSingletonTrait;

    /** @var string Plugin name */
    protected $name = 'mwc-core';

    /** @var array Classes to instantiate */
    protected $classesToInstantiate = [
        CorePaymentGateways::class                             => 'web',
        ExtensionsTab::class                                   => 'web',
        Producers::class                                       => 'web',
        RemoveShipmentTrackingFromManagedWordPressSites::class => 'web',
        ShipmentTracking::class                                => 'web',
        LocalPickup::class                                     => 'web',
        CoreShippingMethods::class                             => 'web',
        Updates::class                                         => 'web',
        Client::class                                          => 'web',
        IncludedWooCommerceExtensionsTab::class                => 'web',
        Notices::class                                         => 'web',
        ViewOrderPage::class                                   => 'web',
        WooCommerceInterceptor::class                          => 'web',

        // GoDaddy\WordPress\MWC\Core\Features
        CostOfGoods::class                                     => true,
        EmailNotifications::class                              => true,
        GoogleAnalytics::class                                 => true,
        // TODO: is this overkill? is there a better place to be loading this? {JS - 2021-10-17}
        OrderSynchronization::class                            => true,
    ];

    /** @var string[] */
    protected $componentClasses = [
        GiftCertificates::class,
        Onboarding::class,
        OnboardingDashboard::class,
        Overrides::class,
        SequentialOrderNumbers::class,
        UrlCoupons::class,
    ];

    /**
     * Package constructor.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        // skip in CLI mode.
        if (! WordPressRepository::isCliMode()) {
            $coreDir = plugin_basename(dirname(__DIR__));

            // load the textdomains
            load_plugin_textdomain('mwc-core', false, $coreDir.'/languages');
            load_plugin_textdomain('mwc-common', false, $coreDir.'/vendor/godaddy/mwc-common/languages');

            // load the dashboard
            Dashboard::getInstance();
        }
    }

    /**
     * Gets configuration values.
     *
     * @return array
     */
    protected function getConfigurationValues() : array
    {
        return array_merge(parent::getConfigurationValues(), [
            'version'    => '2.23.0',
            'plugin_dir' => dirname(__DIR__),
            'plugin_url' => plugin_dir_url(dirname(__FILE__)),
        ]);
    }

    /**
     * Initializes the Configuration class adding the plugin's configuration directory.
     *
     * @since 2.10.0
     */
    protected function initializeConfiguration()
    {
        Configuration::initialize([
            StringHelper::trailingSlash(dirname(__DIR__)).'vendor/godaddy/mwc-shipping/configurations',
            StringHelper::trailingSlash(dirname(__DIR__)).'configurations',
        ]);
    }
}
