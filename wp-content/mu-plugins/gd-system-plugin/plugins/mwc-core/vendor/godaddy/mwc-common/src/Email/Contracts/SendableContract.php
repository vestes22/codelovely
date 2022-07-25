<?php

namespace GoDaddy\WordPress\MWC\Common\Email\Contracts;

/**
 * Interface for objects that can be sent, like an email.
 */
interface SendableContract
{
    /**
     * Sends it.
     */
    public function send();
}
