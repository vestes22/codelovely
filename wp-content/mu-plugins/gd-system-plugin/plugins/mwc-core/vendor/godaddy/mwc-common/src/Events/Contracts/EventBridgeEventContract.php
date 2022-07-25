<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Contracts;

use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Event bridge contract.
 *
 * @see IsEventBridgeEventTrait when implementing some of the interface methods below
 *
 * @since 3.4.1
 */
interface EventBridgeEventContract extends EventContract
{
    /**
     * Gets the name of the resource for the current event.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getResource() : string;

    /**
     * Gets the name of the action for the current event.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getAction() : string;

    /**
     * Gets the data for the current event.
     *
     * @since 3.4.1
     *
     * @return array
     */
    public function getData() : array;
}
