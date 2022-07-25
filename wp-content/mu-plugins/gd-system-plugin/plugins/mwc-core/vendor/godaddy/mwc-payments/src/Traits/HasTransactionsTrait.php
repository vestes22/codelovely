<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * Has transactions trait.
 *
 * @since 0.1.0
 */
trait HasTransactionsTrait
{
    //** @var string transactions gateway class name */
    protected $transactionsGateway;

    /**
     * Gets the transactions gateway instance.
     *
     * @since 0.1.0
     *
     * @return AbstractGateway
     */
    public function transactions() : AbstractGateway
    {
        return new $this->transactionsGateway();
    }
}
