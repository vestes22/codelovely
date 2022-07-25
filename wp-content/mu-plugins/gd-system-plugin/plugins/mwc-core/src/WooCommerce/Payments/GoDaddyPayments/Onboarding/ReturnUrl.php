<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Onboarding;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Cache\Types\CacheOnboardingReturnUrl;

/**
 * Handler for the URL used to redirect merchants after they complete the onboarding process.
 */
class ReturnUrl
{
    /**
     * Gets the URL used to redirect merchants after they complete the onboarding process.
     *
     * @return string
     */
    public static function get() : string
    {
        if ($returnUrl = CacheOnboardingReturnUrl::getInstance()->get()) {
            return $returnUrl;
        }

        return admin_url('admin.php?page=wc-settings&tab=checkout');
    }

    /**
     * Updates the return URL for the onboarding process.
     *
     * We will keep a copy of the most recent return URL used.
     * If no return URL is passed, we will clear the stored value to redirect merchants to the default location.
     *
     * @param string $returnUrl
     */
    public static function update(string $returnUrl)
    {
        if ($returnUrl) {
            CacheOnboardingReturnUrl::getInstance()->set($returnUrl);
        } else {
            CacheOnboardingReturnUrl::getInstance()->clear();
        }
    }
}
