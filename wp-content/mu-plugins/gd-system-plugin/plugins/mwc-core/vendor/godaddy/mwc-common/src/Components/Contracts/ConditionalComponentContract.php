<?php

namespace GoDaddy\WordPress\MWC\Common\Components\Contracts;

/**
 * A conditional component represents functionality that can be loaded when certain conditions are met only.
 *
 * @since x.y.z
 */
interface ConditionalComponentContract extends ComponentContract
{
    /**
     * Determines whether the component should be loaded or not.
     *
     * @since x.y.z
     *
     * @return bool
     */
    public static function shouldLoad() : bool;
}
