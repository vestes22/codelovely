<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\Traits;

use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * A trait that provides a helper method to determine whether a Managed WooCommerce feature should be loaded.
 *
 * TODO: move this trait to mwc-common {wvega 2022-01-05} - https://jira.godaddy.com/browse/MWC-3870
 */
trait IsManagedWooCommerceFeatureTrait
{
    /**
     * Determines whether a feature created for Managed WooCommerce users should load.
     *
     * @return bool
     */
    protected static function shouldLoadManagedWooCommerceFeature() : bool
    {
        return ManagedWooCommerceRepository::hasEcommercePlan()
            && WooCommerceRepository::isWooCommerceActive()
            && ManagedWooCommerceRepository::isAllowedToUseNativeFeatures();
    }
}
