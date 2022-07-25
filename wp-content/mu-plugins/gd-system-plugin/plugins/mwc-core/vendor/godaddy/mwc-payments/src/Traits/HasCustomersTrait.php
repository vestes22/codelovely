<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * Has customers trait.
 *
 * @since 0.1.0
 */
trait HasCustomersTrait
{
    /** @var string customers gateway class */
    protected $customersGateway;

    /**
     * Gets the customers gateway instance.
     *
     * @since 0.1.0
     *
     * @return AbstractGateway
     */
    public function customers() : AbstractGateway
    {
        return new $this->customersGateway();
    }
}
