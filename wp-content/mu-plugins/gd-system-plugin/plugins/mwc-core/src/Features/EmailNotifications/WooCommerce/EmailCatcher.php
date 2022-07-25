<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailSendFailedException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce\EmailNotificationAdapter;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailBuilder;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetEmailNotificationDataStoreTrait;
use InvalidArgumentException;
use WC_Email;

/**
 * Email catcher class.
 */
class EmailCatcher implements ConditionalComponentContract
{
    use CanGetEmailNotificationDataStoreTrait;

    /**
     * Processes a WooCommerce email to send when invoked by WooCommerce as a function.
     *
     * TODO: catch Exception instances  that we throw or replace them with custom exceptions (See MWC-2767) {wvega 2021-10-08}
     *
     * @see WC_Email::send()
     * @see EmailCatcher::filterMailCallback()
     * @see EmailCatcher::filterMailCallbackParameters()
     */
    public function __invoke(WC_Email $email)
    {
        try {
            try {
                $this->getEmailBuilder($this->getAdaptedEmailNotification($email))
                    ->setHeaders(EmailNotificationAdapter::getEmailHeaders($email))
                    ->setFromAddress(EmailNotificationAdapter::getEmailSenderAddress($email))
                    ->setRecipients(EmailNotificationAdapter::getEmailRecipients($email))
                    ->setAttachments(EmailNotificationAdapter::getEmailAttachments($email))
                    ->build()
                    ->send();
            } catch (Exception $exception) {
                // there was an error trying to prepare the email
                throw new EmailSendFailedException($exception->getMessage());
            }
        } catch (EmailSendFailedException $e) {
            // there was an error sending the email: the exception will be reported to sentry
        }
    }

    /**
     * Gets an adapted email notification for a given WooCommerce email.
     *
     * @param WC_Email $email
     * @return EmailNotificationContract
     * @throws InvalidArgumentException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     */
    protected function getAdaptedEmailNotification(WC_Email $email) : EmailNotificationContract
    {
        try {
            $emailNotificationId = EmailNotificationAdapter::getEmailNotificationId($email);
            $emailNotification = $this->getEmailNotificationDataStore()->read($emailNotificationId);
        } catch (EmailNotificationNotFoundException $exception) {
            $emailNotification = null;
        }

        return $this->getEmailNotificationAdapter($email)->convertFromSource($emailNotification);
    }

    /**
     * Gets a new email notification adapter for a given WooCommerce email object as the source.
     *
     * @param WC_Email $email
     * @return EmailNotificationAdapter
     */
    protected function getEmailNotificationAdapter(WC_Email $email) : EmailNotificationAdapter
    {
        return EmailNotificationAdapter::from($email);
    }

    /**
     * Gets a new email builder instance for a given email notification object.
     *
     * @param EmailNotificationContract $emailNotification
     * @return EmailBuilder
     */
    protected function getEmailBuilder(EmailNotificationContract $emailNotification) : EmailBuilder
    {
        return new EmailBuilder($emailNotification);
    }

    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        $this->addHooks();
    }

    /**
     * Adds action and filter hooks.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_mail_callback')
            ->setHandler([$this, 'filterMailCallback'])
            ->setArgumentsCount(2)
            ->execute();

        Register::filter()
            ->setGroup('woocommerce_mail_callback_params')
            ->setHandler([$this, 'filterMailCallbackParameters'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Filters the callback of a WooCommerce email.
     *
     * @internal
     * @see WC_Email::send()
     * @see EmailCatcher::addHooks()
     * @see EmailCatcher::__invoke()
     *
     * @param string|array|object|Closure $callback
     * @param WC_Email|mixed $email
     * @return string|self
     */
    public function filterMailCallback($callback, $email)
    {
        return $this->shouldFilterEmailHandling($email)
            ? $this
            : $callback;
    }

    /**
     * Filters the params of a WooCommerce email.
     *
     * @internal
     * @see WC_Email::send()
     * @see EmailCatcher::addHooks()
     * @see EmailCatcher::__invoke()
     *
     * @param array|mixed $params
     * @param WC_Email|mixed $email
     * @return array|mixed
     */
    public function filterMailCallbackParameters($params, $email)
    {
        return $this->shouldFilterEmailHandling($email)
            ? [$email]
            : $params;
    }

    /**
     * Determines whether the WooCommerce email handling should be filtered.
     *
     * @param WC_Email|mixed $email
     * @return bool
     */
    protected function shouldFilterEmailHandling($email) : bool
    {
        return $email instanceof WC_Email && isset($email->id);
    }

    /**
     * Determines whether the component should be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoad(): bool
    {
        return EmailNotifications::isEnabled();
    }
}
