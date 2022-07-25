<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Traits\IsManagedWooCommerceFeatureTrait;

class Dashboard implements ConditionalComponentContract
{
    use HasComponentsTrait;
    use IsManagedWooCommerceFeatureTrait;

    /** @var array alphabetically ordered list of components to load */
    protected $componentClasses = [
        HomePage::class,
    ];

    /**
     * Initializes the feature.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->loadComponents();
    }

    /**
     * Determines whether the Dashboard feature should load.
     *
     * @return bool
     */
    public static function shouldLoad() : bool
    {
        return Configuration::get('features.onboarding_dashboard.enabled', false)
            && static::shouldLoadManagedWooCommerceFeature();
    }
}
