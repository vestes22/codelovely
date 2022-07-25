<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;

/**
 * Managed WooCommerce repository class.
 */
class ManagedWooCommerceRepository
{
    /**
     * Gets the current Managed WordPress environment.
     *
     * @return string|null
     */
    public static function getEnvironment()
    {
        if (Configuration::get('mwc.env')) {
            return Configuration::get('mwc.env');
        }

        if (Configuration::get('godaddy.is_staging_site')) {
            return 'staging';
        }

        /** @TODO: Figure out how to determine it is a local env */
        // return 'development';

        $env = 'production';

        Configuration::set('mwc.env', $env);

        return $env;
    }

    /**
     * Determines if the current is a production environment.
     *
     * @return bool
     */
    public static function isProductionEnvironment() : bool
    {
        return 'production' === self::getEnvironment();
    }

    /**
     * Determines if the current environment is a staging environment.
     *
     * @return bool
     */
    public static function isStagingEnvironment() : bool
    {
        return 'staging' === self::getEnvironment();
    }

    /**
     * Determines if the current is a local environment.
     *
     * @return bool
     */
    public static function isLocalEnvironment() : bool
    {
        return 'development' === self::getEnvironment();
    }

    /**
     * Determines if the current is a testing environment.
     *
     * @return bool
     */
    public static function isTestingEnvironment() : bool
    {
        return 'testing' === self::getEnvironment();
    }

    /**
     * Determines if the site is hosted on Managed WordPress and has an eCommerce plan.
     *
     * @return bool
     */
    public static function hasEcommercePlan() : bool
    {
        $godaddy_expected_plan = Configuration::get('godaddy.account.plan.name');

        return self::isManagedWordPress() && $godaddy_expected_plan === Configuration::get('mwc.plan_name');
    }

    /**
     * Determines if the site is hosted on Managed WordPress and has a a Pro plan.
     *
     * @return bool
     */
    public static function hasProPlan() : bool
    {
        return StringHelper::startsWith(static::getManagedWordPressPlan() ?: '', 'pro');
    }

    /**
     * Determines if the site is hosted on Managed WordPress.
     *
     * @return bool
     */
    public static function isManagedWordPress() : bool
    {
        return (bool) Configuration::get('godaddy.account.uid');
    }

    /**
     * Determines the identifier of the Managed WordPress hosting plan used by this site.
     *
     * @return string|null
     */
    public static function getManagedWordPressPlan()
    {
        if (! static::isManagedWordPress()) {
            return null;
        }

        $accountPlanName = strtolower(Configuration::get('godaddy.account.plan.name'));

        foreach (ArrayHelper::wrap(Configuration::get('mwp.hosting.plans')) as $id => $plan) {
            if ($accountPlanName === strtolower(ArrayHelper::get($plan, 'name', ''))) {
                return $id;
            }
        }

        // assume that the account is using the smaller hosting plan if we can't determine one
        return 'basic';
    }

    /**
     * Determines whether the site can use native features.
     *
     * A site can use native features if it's not on a reseller account or it's configured to allow native features for resellers.
     *
     * @return bool
     */
    public static function isAllowedToUseNativeFeatures() : bool
    {
        return ! static::isReseller() || Configuration::get('mwc.allow_native_features_for_resellers');
    }

    /**
     * Determines if the site is hosted on MWP and sold by a reseller.
     *
     * @return bool
     */
    public static function isReseller() : bool
    {
        return self::isManagedWordPress() && (int) self::getResellerId() > 1;
    }

    /**
     * Gets the configured reseller account, if present.
     *
     * `1` means the site is not a reseller site, but sold directly by GoDaddy.
     *
     * @return int|null
     */
    public static function getResellerId()
    {
        return Configuration::get('godaddy.reseller');
    }

    /**
     * Determines if the site is hosted on MWP and sold by a reseller with support agreement.
     *
     * @return bool
     */
    public static function isResellerWithSupportAgreement() : bool
    {
        if (! self::isReseller()) {
            return false;
        }

        return ! ArrayHelper::get(self::getResellerSettings(), 'customerSupportOptOut', true);
    }

    /**
     * Gets settings for a reseller account.
     *
     * @return array
     */
    private static function getResellerSettings() : array
    {
        try {
            $settings = (new Request())
                ->url(StringHelper::trailingSlash(static::getStorefrontSettingsApiUrl()).static::getResellerId())
                ->query(['fields' => 'customerSupportOptOut'])
                ->send()
                ->getBody();
        } catch (Exception $e) {
            $settings = [];
        }

        return ArrayHelper::wrap($settings);
    }

    /**
     * Gets the Storefront Settings API URL.
     *
     * @return string
     */
    private static function getStorefrontSettingsApiUrl()
    {
        return StringHelper::trailingSlash(Configuration::get('mwc.extensions.api.url', ''))
            .Configuration::get('mwc.extensions.api.settings.reseller.endpoint', '');
    }

    /**
     * Determines if the site is hosted on MWP and is using a temporary domain.
     *
     * @return bool
     */
    public static function isTemporaryDomain() : bool
    {
        $domain = Configuration::get('godaddy.temporary_domain');
        $homeUrl = parse_url(SiteRepository::getHomeUrl(), PHP_URL_HOST);

        return self::isManagedWordPress() && is_string($domain) && is_string($homeUrl) && StringHelper::trailingSlash($domain) === StringHelper::trailingSlash($homeUrl);
    }

    /**
     * Determines if the site used the WPNux template on-boarding system.
     *
     * @return bool
     */
    public static function hasCompletedWPNuxOnboarding() : bool
    {
        return WordPressRepository::hasWordPressInstance() && (bool) get_option('wpnux_imported');
    }

    /**
     * Gets the value of the XID server variable.
     *
     * @return int
     */
    public static function getXid() : int
    {
        $siteXId = ArrayHelper::exists($_SERVER, 'XID')
            ? (int) ArrayHelper::get($_SERVER, 'XID', 0)
            : (int) ArrayHelper::get($_SERVER, 'WPAAS_SITE_ID', 0);

        return $siteXId > 1000000 ? $siteXId : 0;
    }

    /**
     * Gets the ID for the site.
     *
     * @return string
     */
    public static function getSiteId() : string
    {
        $siteId = Configuration::get('godaddy.site.id');

        if (empty($siteId) && $siteXid = (string) self::getXid()) {
            // use XID instead
            $siteId = $siteXid;

            // update configuration
            Configuration::set('godaddy.site.id', $siteId);

            if (WordPressRepository::hasWordPressInstance()) {
                update_option('gd_mwc_site_id', $siteId);
            }
        }

        return $siteId;
    }
}
