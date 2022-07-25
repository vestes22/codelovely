<?php

namespace GoDaddy\WordPress\MWC\Common\Email\Contracts;

/**
 * The contract for email notifications.
 */
interface EmailContract extends SendableContract
{
    /**
     * Get the email the request is coming from.
     *
     * @return string|null
     */
    public function getFrom();

    /**
     * Get the email the request is going to.
     *
     * @return string[]|string
     */
    public function getTo();

    /**
     * Get the email subject.
     *
     * @return string|null
     */
    public function getSubject();

    /**
     * Get the email body.
     *
     * @return string
     */
    public function getBody() : string;

    /**
     * Get the email alternate body.
     *
     * @return string
     */
    public function getAltBody() : string;

    /**
     * Get the email headers.
     *
     * @return array
     */
    public function getHeaders() : array;

    /**
     * Get the email content type.
     *
     * @return string
     */
    public function getContentType() : string;

    /**
     * Get the email body format type: html, plain-text, etc.
     *
     * @return string
     */
    public function getBodyFormat() : string;

    /**
     * Get the name of the email -- this usually refers to the template name/type.
     *
     * @return string|null
     */
    public function getEmailName();

    /**
     * Get the sender's name the request is coming from.
     *
     * @return string|null
     */
    public function getFromName();

    /**
     * Gets the email attachments list.
     *
     * @return array
     */
    public function getAttachments() : array;

    /**
     * Add an email header to the headers.
     *
     * @param string $name
     * @param string $value
     * @return self
     */
    public function addHeader(string $name, string $value);

    /**
     * Remove a header from the email headers.
     *
     * @param string $value
     * @return self
     */
    public function removeHeader(string $value);

    /**
     * Set the email the request is coming from.
     *
     * @param string $value
     * @return self
     */
    public function setFrom(string $value);

    /**
     * Get the sender's name the request is coming from.
     *
     * @param string $value
     * @return self
     */
    public function setFromName(string $value);

    /**
     * Set the email the request is going to.
     *
     * @param string[]|string $value
     * @return self
     */
    public function setTo($value);

    /**
     * Set the email subject.
     *
     * @param string $value
     * @return self
     */
    public function setSubject(string $value);

    /**
     * Set the email body.
     *
     * @param string $value
     * @return self
     */
    public function setBody(string $value);

    /**
     * Set the email alternate body.
     *
     * @param string $value
     * @return self
     */
    public function setAltBody(string $value);

    /**
     * Set the email headers.
     *
     * @param array $value
     * @return self
     */
    public function setHeaders(array $value);

    /**
     * Set the email content type.
     *
     * @param string $value
     * @return self
     */
    public function setContentType(string $value);

    /**
     * Set the email name.
     *
     * @param string $value
     * @return self
     */
    public function setEmailName(string $value);

    /**
     * Sets the email attachments list.
     *
     * @param array $attachments
     * @return self
     */
    public function setAttachments(array $value);
}
