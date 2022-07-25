<?php

namespace GoDaddy\WordPress\MWC\Shipping\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;

/**
 * Package status contract.
 *
 * @since 0.1.0
 */
interface PackageStatusContract extends HasLabelContract
{
    /**
     * Determines whether the status can fulfill items in the package.
     *
     * @since 0.1.0
     *
     * @return bool
     */
    public function canFulfillItems() : bool;
}
