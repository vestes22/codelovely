<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;

/**
 * A trait for singletons.
 *
 * @since 1.0.0
 */
trait IsSingletonTrait
{
    /** @var AbstractExtension holds the current singleton instance */
    protected static $instance;

    /**
     * Determines if the current instance is loaded.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function isLoaded() : bool
    {
        return (bool) static::$instance;
    }

    /**
     * Gets the singleton instance.
     *
     * @since 1.0.0
     *
     * @return AbstractExtension
     */
    public static function getInstance()
    {
        if (! static::isLoaded()) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Resets the singleton instance.
     *
     * @since 1.0.0
     */
    public static function reset()
    {
        static::$instance = null;
    }
}
