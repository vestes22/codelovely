<?php

namespace GoDaddy\WordPress\MWC\Common\Register\Contracts;

/**
 * Something that can be registered, like a static asset, script or style.
 */
interface RegistrableContract
{
    /**
     * Sets the registrable type.
     */
    public function __construct();

    /**
     * Determines how to deregister the registrable object.
     */
    public function deregister();

    /**
     * Determines how to execute the register.
     */
    public function execute();

    /**
     * Validates the current instance settings.
     */
    public function validate();
}
