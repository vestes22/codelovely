<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ConditionalComponentContract;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Email\RenderableEmail;

/**
 * Email catcher class.
 */
class EmailCatcher implements ConditionalComponentContract
{
    /** @var bool whether we should try to catch emails */
    protected static $enabled = true;

    /**
     * Configures all instances of this class to try to catch emails send using wp_mail().
     */
    public static function enable()
    {
        static::$enabled = true;
    }

    /**
     * Configures all instances of this class to stop trying to catch emails sent using wp_mail().
     */
    public static function disable()
    {
        static::$enabled = false;
    }

    /**
     * Adds action and filter hooks.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::filter()
            ->setGroup('pre_wp_mail')
            ->setHandler([$this, 'maybeSendEmail'])
            ->setArgumentsCount(2)
            ->execute();
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
     * Determines whether the component should be loaded.
     *
     * @return bool
     * @throws Exception
     */
    public static function shouldLoad() : bool
    {
        return Configuration::get('features.email_deliverability')
            && ! Conflicts::hasConflict();
    }

    /**
     * Maybe intercept email handling through the pre-sending WP Mail filter.
     *
     * @internal callback for pre_wp_mail
     * @see EmailCatcher::addHooks()
     *
     * @param bool|null $shortCircuitReturn
     * @param array $emailAttributes
     * @return bool|null
     */
    public function maybeSendEmail($shortCircuitReturn, $emailAttributes)
    {
        if (! static::$enabled) {
            return $shortCircuitReturn;
        }

        try {
            $headers = $this->getEmailHeaders($emailAttributes);

            (new RenderableEmail($this->getEmailRecipients($emailAttributes)))
                ->setEmailName('wp_system_email')
                ->setSubject(ArrayHelper::get($emailAttributes, 'subject', ''))
                ->setHeaders($headers)
                ->setContentType($this->getContentType($headers))
                ->setBody(ArrayHelper::get($emailAttributes, 'message', ''))
                ->setAttachments(ArrayHelper::wrap(ArrayHelper::get($emailAttributes, 'attachments', [])))
                ->send();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets the recipients from the given email attributes.
     *
     * @param array $emailAttributes
     * @return array
     */
    protected function getEmailRecipients($emailAttributes) : array
    {
        $recipients = ArrayHelper::get($emailAttributes, 'to', '');

        if (is_string($recipients)) {
            $recipients = explode(',', $recipients);
        }

        return array_values(array_filter(array_map('trim', $recipients)));
    }

    /**
     * Gets the headers from the given email attributes.
     *
     * If the headers attribute is a string, it splits the string into an array of headers.
     *
     * @param array $emailAttributes
     * @return array
     */
    protected function getEmailHeaders($emailAttributes) : array
    {
        $headers = ArrayHelper::get($emailAttributes, 'headers', []);

        if (! ArrayHelper::accessible($headers)) {
            $headers = explode("\n", str_replace("\r\n", "\n", $headers));
        }

        return array_reduce($headers, function ($result, $header) {
            if (strpos($header, ':') === false) {
                return $result;
            }

            $result[trim(StringHelper::before($header, ':'))] = trim(StringHelper::after($header, ':'));

            return $result;
        }, []);
    }

    /**
     * Gets the content type for the email.
     *
     * @param array $headers email headers
     * @return string
     */
    protected function getContentType(array $headers) : string
    {
        // The wp_mail_content_type filter is documented in wp-includes/pluggable.php.
        return apply_filters('wp_mail_content_type', $this->getContentTypeFromHeaders($headers) ?: 'text/plain');
    }

    /**
     * Gets the content type from the email headers.
     *
     * @param array $headers email headers
     * @return string|null
     */
    protected function getContentTypeFromHeaders(array $headers)
    {
        foreach ($headers as $header => $value) {
            if (strtolower($header) !== 'content-type') {
                continue;
            }

            if (! $contentType = explode(';', $value)[0]) {
                continue;
            }

            return $contentType;
        }

        return null;
    }
}
