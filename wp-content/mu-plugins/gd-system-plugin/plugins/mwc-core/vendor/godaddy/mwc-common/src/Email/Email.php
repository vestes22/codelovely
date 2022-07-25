<?php

namespace GoDaddy\WordPress\MWC\Common\Email;

use Exception;
use GoDaddy\WordPress\MWC\Common\Email\Contracts\EmailContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use InvalidArgumentException;

/**
 * Email class.
 */
class Email implements EmailContract
{
    /** @var string[]|string recipients' email */
    protected $to;

    /** @var string|null sender's email */
    protected $from;

    /** @var string|null sender's name */
    protected $fromName;

    /** @var string|null email subject */
    protected $subject;

    /** @var string email body */
    protected $body = '';

    /** @var string email alternative body */
    protected $altBody = '';

    /** @var array normally a key-value array of headers */
    protected $headers = [];

    /** @var string content type, sent as header and used by the {@see Email::getContentType()} callback */
    protected $contentType = 'text/html';

    /** @var string|null the name of the email -- usually the template type/name */
    protected $emailName;

    /** @var string[] email attachments list */
    protected $attachments = [];

    /**
     * Email constructor.
     *
     * @param string[]|string $to recipient's email
     * @throws InvalidArgumentException
     */
    public function __construct($to)
    {
        $this->setTo($to);
    }

    /**
     * Sets the recipient's email.
     *
     * @param string[]|string $value
     * @return self
     * @throws InvalidArgumentException
     */
    public function setTo($value) : Email
    {
        if (! is_array($value) && ! is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                __('Invalid recipient value to set for email: should be type of string or array, %s given.', 'mwc-common'),
                gettype($value)
            ));
        }

        $this->to = $value;

        return $this;
    }

    /**
     * Sets the sender's email.
     *
     * @param string $value
     * @return self
     */
    public function setFrom(string $value) : Email
    {
        $this->from = $value;

        return $this;
    }

    /**
     * Sets the email subject.
     *
     * @param string $value
     * @return self
     */
    public function setSubject(string $value) : Email
    {
        $this->subject = $value;

        return $this;
    }

    /**
     * Sets the email body.
     *
     * @param string $value
     * @return self
     */
    public function setBody(string $value) : Email
    {
        $this->body = $value;

        return $this;
    }

    /**
     * Sets the email alternative body.
     *
     * @param string $value
     * @return self
     */
    public function setAltBody(string $value) : Email
    {
        $this->altBody = $value;

        return $this;
    }

    /**
     * Sets the email headers.
     *
     * @param array $value
     * @return self
     */
    public function setHeaders(array $value) : Email
    {
        $this->headers = $value;

        return $this;
    }

    /**
     * Sets the email name.
     *
     * @param string $value
     * @return self
     */
    public function setEmailName(string $value) : Email
    {
        $this->emailName = $value;

        return $this;
    }

    /**
     * Sets the email content type and adds a header for it.
     *
     * @param string $value
     * @return Email
     * @throws Exception
     */
    public function setContentType(string $value) : Email
    {
        $this->contentType = $value;

        // TODO: let the email service responsible for sending the email handle the Content-Type header {wvega 2021-09-15}
        $this->headers = ArrayHelper::combine($this->headers ?: [], ['Content-type' => $value]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setFromName(string $value)
    {
        $this->fromName = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachments(array $value)
    {
        $this->attachments = $value;

        return $this;
    }

    /**
     * Adds a header to the email.
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function addHeader(string $name, string $value) : Email
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Removes a header from the email.
     *
     * @param string $value
     * @return self
     */
    public function removeHeader(string $value) : Email
    {
        unset($this->headers[$value]);

        return $this;
    }

    /**
     * Gets the recipients' email.
     *
     * @return string[]|string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Gets the sender's email.
     *
     * @return string|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Gets the email subject.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Gets the email body.
     *
     * @return string
     */
    public function getBody() : string
    {
        return $this->body;
    }

    /**
     * Gets the email alternative body.
     *
     * @return string
     */
    public function getAltBody() : string
    {
        return $this->altBody;
    }

    /**
     * Gets the email headers.
     *
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Gets the email content type.
     *
     * @return string
     */
    public function getContentType() : string
    {
        return $this->contentType;
    }

    /**
     * Get the email body format type: html, plain-text, etc.
     *
     * @return string
     */
    public function getBodyFormat() : string
    {
        return ArrayHelper::get([
            'text/html'  => 'html',
            'text/plain' => 'plain',
        ], $this->getContentType(), 'html');
    }

    /**
     * Get the name of the email -- this usually refers to the template name/type.
     *
     * @return string|null
     */
    public function getEmailName()
    {
        return $this->emailName;
    }

    /**
     * {@inheritdoc}
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * Sends the email.
     *
     * @throws Exception
     */
    public function send()
    {
        Emails::send($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachments() : array
    {
        return $this->attachments;
    }
}
