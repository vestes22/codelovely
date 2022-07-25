<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\ValidationHelper;
use GoDaddy\WordPress\MWC\Core\Email\RenderableEmail;

/**
 * A builder for converting email notification definitions into email objects.
 */
class EmailBuilder extends AbstractEmailBuilder
{
    /** @var string */
    protected $fromAddress;

    /** @var string */
    protected $fromName;

    /** @var string[] */
    protected $headers = [];

    /** @var string[] */
    protected $recipients = [];

    /**
     * Sets the email's from address.
     *
     * @param string $value
     * @return self
     */
    public function setFromAddress(string $value) : EmailBuilder
    {
        $this->fromAddress = $value;

        return $this;
    }

    /**
     * Sets the email's from name.
     *
     * @param string $value
     * @return self
     */
    public function setFromName(string $value) : EmailBuilder
    {
        $this->fromName = $value;

        return $this;
    }

    /**
     * Sets the email's headers.
     *
     * @param array $value
     * @return self
     */
    public function setHeaders(array $value) : EmailBuilder
    {
        $this->headers = $value;

        return $this;
    }

    /**
     * Sets the email's recipient addresses.
     *
     * @param array $value
     * @return self
     */
    public function setRecipients(array $value) : EmailBuilder
    {
        $this->recipients = ArrayHelper::where($value, static function ($address) {
            return ValidationHelper::isEmail($address);
        });

        return $this;
    }

    /**
     * Builds the email object.
     *
     * @return RenderableEmail
     * @throws Exception
     */
    public function build() : RenderableEmail
    {
        $email = parent::build();

        return $email->setHeaders($this->getFormattedHeaders());
    }

    /**
     * Gets the email notification data.
     *
     * @return array
     */
    protected function getEmailNotificationData() : array
    {
        return $this->emailNotification->getData();
    }

    /**
     * Gets a new email instance.
     *
     * @return RenderableEmail
     */
    protected function getNewInstance() : RenderableEmail
    {
        return new RenderableEmail($this->getFormattedRecipients());
    }

    /**
     * Gets the formatted headers, ready for sending.
     *
     * This adds the Reply-to header, derived from $fromName & $fromAddress.
     *
     * @return string[]
     */
    protected function getFormattedHeaders() : array
    {
        $formattedHeaders = $this->headers;

        if ($this->fromAddress && $this->fromName && ! ArrayHelper::has($formattedHeaders, 'Reply-to')) {
            $formattedHeaders['Reply-to'] = "{$this->fromName} <{$this->fromAddress}>";
        } elseif ($this->fromAddress && ! ArrayHelper::has($formattedHeaders, 'Reply-to')) {
            $formattedHeaders['Reply-to'] = $this->fromAddress;
        }

        return $formattedHeaders;
    }

    /**
     * Gets the formatted list of recipient addresses.
     *
     * @return string
     */
    protected function getFormattedRecipients() : string
    {
        return implode(',', $this->recipients);
    }
}
