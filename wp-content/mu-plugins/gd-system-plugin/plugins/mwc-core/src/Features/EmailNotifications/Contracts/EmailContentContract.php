<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;

/**
 * Interface for objects representing an email content.
 */
interface EmailContentContract extends ConfigurableContract
{
    /**
     * Gets the email content ID.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Gets the email content structured content.
     *
     * @return string
     */
    public function getStructuredContent() : string;

    /**
     * Gets the path to the file to load structured content from.
     *
     * @return string;
     */
    public function getStructuredContentPath() : string;

    /**
     * Gets the email content plain content.
     *
     * @return string
     */
    public function getPlainContent() : string;

    /**
     * Sets the email content ID.
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value);

    /**
     * Sets the path to the file to load structured content from.
     *
     * @param string $value
     * @return self
     */
    public function setStructuredContentPath(string $value);
}
