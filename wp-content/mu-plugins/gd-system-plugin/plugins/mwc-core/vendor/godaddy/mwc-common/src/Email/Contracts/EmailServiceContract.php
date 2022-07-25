<?php

namespace GoDaddy\WordPress\MWC\Common\Email\Contracts;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;

/**
 * An interface for email services.
 */
interface EmailServiceContract extends ComponentContract
{
    /**
     * Sends an email.
     *
     * @param EmailContract $email
     */
    public function send(EmailContract $email);
}
