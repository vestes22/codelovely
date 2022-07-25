<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageStatusContract;

/**
 * Status for cancelled packages.
 *
 * @since 0.1.0
 */
class CancelledPackageStatus implements PackageStatusContract
{
    use HasLabelTrait;

    /**
     * Cancelled package status constructor.
     *
     * Initializes the status by setting its name and label.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->setName('cancelled');
        $this->setLabel(__('Cancelled', 'mwc-shipping'));
    }

    /**
     * Determines whether the status can fulfill items in the package.
     *
     * A cancelled package cannot fulfill items.
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
