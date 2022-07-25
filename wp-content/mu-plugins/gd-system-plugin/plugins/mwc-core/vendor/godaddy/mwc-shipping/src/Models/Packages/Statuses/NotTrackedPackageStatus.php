<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageStatusContract;

/**
 * Status for packages not being tracked.
 *
 * @since 0.1.0
 */
class NotTrackedPackageStatus implements PackageStatusContract
{
    use HasLabelTrait;

    /**
     * Not tracked package status constructor.
     *
     * Initializes the status by setting its name and label.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->setName('not-tracked');
        $this->setLabel(__('Not Tracked', 'mwc-shipping'));
    }

    /**
     * Determines whether the status can fulfill items in the package.
     *
     * A package that is not tracked cannot fulfill items.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function canFulfillItems(): bool
    {
        return false;
    }
}
