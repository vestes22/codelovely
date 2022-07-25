<?php

namespace GoDaddy\WordPress\MWC\Common\Enqueue\Contracts;

/**
 * Something that can be enqueued, like a static asset, script or style.
 *
 * @since 1.0.0
 */
interface EnqueuableContract
{
    /**
     * Sets the enqueue type.
     *
     * @since 1.0.0
     */
    public function __construct();

    /**
     * Registers and enqueues the asset in WordPress.
     *
     * @since 1.0.0
     */
    public function execute();

    /**
     * Validates the current instance settings.
     *
     * @since 1.0.0
     */
    public function validate();
}
