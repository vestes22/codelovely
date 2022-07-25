<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\Contracts;

/**
 * The contract for interceptor classes.
 *
 * Classes implementing this interface are able to hook into actions and filters to intercept WooCommerce actions.
 */
interface InterceptorContract
{
    /**
     * Should implement action and filter hooks.
     */
    public function addHooks();
}
