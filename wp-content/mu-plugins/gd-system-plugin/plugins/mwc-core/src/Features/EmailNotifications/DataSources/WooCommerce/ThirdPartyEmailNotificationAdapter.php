<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\DefaultEmailContent;
use InvalidArgumentException;
use Throwable;
use WC_Email;

/**
 * An adapter for third party WooCommerce emails.
 */
class ThirdPartyEmailNotificationAdapter extends EmailNotificationAdapter
{
    /**
     * Adapts an email notification subject based on the subject of a WooCommerce email.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function adaptEmailSubject(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        if (! $emailNotification->getSubject()) {
            $value = $this->callMethodIgnoringErrors($email, 'get_subject');

            if (! is_null($value)) {
                $emailNotification->setSubject($value);
            }
        }

        return $emailNotification;
    }

    /**
     * Calls a method on the given WooCommerce email object catching any exceptions and errors thrown in the process.
     *
     * @param WC_Email $email
     * @param string $method the name of the method
     * @param array $args arguments for the method
     * @return mixed
     */
    protected function callMethodIgnoringErrors(WC_Email $email, string $method, ...$args)
    {
        try {
            return @$email->{$method}(...$args);
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     * Adapts an email notification's content values from a corresponding WooCommerce email's content values.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function adaptEmailContent(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        if ($content = $emailNotification->getContent()) {
            foreach ($content->getSettings() as $setting) {
                if (! $setting->hasValue()) {
                    if (DefaultEmailContent::SETTING_ID_HEADING === $setting->getId()) {
                        $value = $this->callMethodIgnoringErrors($email, 'get_heading');

                        if (! is_null($value)) {
                            $this->updateSettingValueIgnoringErrors($setting, $value);
                        }
                    } elseif (DefaultEmailContent::SETTING_ID_ADDITIONAL_CONTENT === $setting->getId()) {
                        $value = $this->callMethodIgnoringErrors($email, 'get_additional_content');

                        if (! is_null($value)) {
                            $this->updateSettingValueIgnoringErrors($setting, $value);
                        }
                    }
                }
            }
        }

        return $emailNotification;
    }

    /**
     * Sets the value of a setting catching any InvalidArgumentException exceptions thrown in the process.
     *
     * @param SettingContract $setting
     * @param mixed $value
     */
    protected function updateSettingValueIgnoringErrors(SettingContract $setting, $value)
    {
        try {
            $setting->setValue($value);
        } catch (InvalidArgumentException $exception) {
            // if an error occurs we let the adapter move to the next setting
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function convertEmailNotificationToSource(EmailNotificationContract $emailNotification = null) : WC_Email
    {
        $email = static::getEmail($emailNotification);

        return $this->adaptEmailNotificationEnabled($emailNotification, $email);
    }
}
