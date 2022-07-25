<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Packages\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Shipping\Contracts\PackageStatusContract;

/**
 * Status for packages with label created.
 *
 * @since 0.1.0
 */
class LabelCreatedPackageStatus implements PackageStatusContract
{
    use HasLabelTrait;

    /**
     * Label created package status constructor.
     *
     * Initializes the status by setting its name and label.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->setName('label-created');
        $this->setLabel(__('Label Created', 'mwc-shipping'));
    }

    /**
     * Determines whether the status can fulfill items in the package.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function canFulfillItems(): bool
    {
        return true;
    }
}
