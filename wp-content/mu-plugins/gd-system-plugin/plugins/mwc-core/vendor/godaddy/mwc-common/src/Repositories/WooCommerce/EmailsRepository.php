<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Email;
use WC_Emails;

/**
 * A repository for handling WooCommerce emails.
 */
class EmailsRepository
{
    /**
     * Gets the WooCommerce Emails handler.
     *
     * @return WC_Emails|null
     * @throws \Exception
     */
    public static function mailer()
    {
        return ! empty($wc = WooCommerceRepository::getInstance()) ? $wc->mailer() : null;
    }

    /**
     * Gets a WooCommerce Email object for a given identifier, if found.
     *
     * @param string $identifier ID or class name
     * @return WC_Email|null
     */
    public static function get(string $identifier)
    {
        $emails = static::all();

        // try by class name
        if (class_exists($identifier) && is_a($identifier, WC_Email::class, true)) {
            foreach ($emails as $email) {
                if (is_object($email) && $identifier === get_class($email)) {
                    return $email;
                }
            }
        }

        // try by email ID
        return $emails[$identifier] ?? null;
    }

    /**
     * Gets all WooCommerce Emails.
     *
     * @return WC_Email[] associative array of email IDs and objects
     */
    public static function all() : array
    {
        $emails = [];

        if (! empty($mailer = static::mailer())) {
            foreach ($mailer->get_emails() as $email) {
                $emails[$email->id] = $email;
            }
        }

        return $emails;
    }
}
