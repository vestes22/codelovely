<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations\Contracts;

/**
 * Interface IntegrationContract.
 *
 * @since 2.10.0
 */
interface IntegrationContract
{
    /**
     * Get the integration's supports.
     *
     * @return string[]
     */
    public function getSupports() : array;
}
