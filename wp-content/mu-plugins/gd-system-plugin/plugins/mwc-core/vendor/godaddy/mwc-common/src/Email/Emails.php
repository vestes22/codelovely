<?php

namespace GoDaddy\WordPress\MWC\Common\Email;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailServiceContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use InvalidArgumentException;

/**
 * Emails class.
 */
class Emails
{
    use HasComponentsTrait;

    /**
     * Sends the email based on the EmailService stored in configuration.
     *
     * @param EmailContract $email
     * @throws Exception
     */
    public static function send(EmailContract $email)
    {
        $emailService = static::getEmailServiceForEmail($email);
        $emailService->send($email);
    }

    /**
     * Returns and instantiates an object based on the $email content type.
     *
     * @param EmailContract $email
     * @return EmailServiceContract
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected static function getEmailServiceForEmail(EmailContract $email) : EmailServiceContract
    {
        $contentType = $email->getContentType();

        if (! $contentType) {
            throw new Exception(__('The email does not have content type set', 'mwc-common'));
        }

        foreach (static::getPossibleEmailServices($contentType) as $service) {
            if (static::isEmailService($service) && $instance = static::maybeLoadComponent($service)) {
                return $instance;
            }
        }

        throw new Exception(sprintf(__('A usable email service could not be found for %s', 'mwc-common'), $contentType));
    }

    /**
     * Determines if a class is a valid email service.
     *
     * @param string $class
     * @return bool
     */
    protected static function isEmailService(string $class) : bool
    {
        return is_a($class, EmailServiceContract::class, true);
    }

    /**
     * Returns the email services which can possibly be instantiated for a given content type.
     *
     * @param string $contentType
     * @return array
     * @throws Exception
     */
    protected static function getPossibleEmailServices(string $contentType) : array
    {
        return ArrayHelper::wrap(Configuration::get('email.services.'.$contentType));
    }
}
