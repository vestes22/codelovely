<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Common\Settings\Traits\HasSettingsTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailContentContract;

/**
 * A model object for loading the structured content from a file included with the plugin.
 */
abstract class AbstractFileEmailContent implements EmailContentContract
{
    use CanConvertToArrayTrait;
    use HasSettingsTrait;
    use HasLabelTrait;

    /** @var string identifier */
    protected $id = '';

    /** @var string path to file */
    protected $structuredContentPath = '';

    /**
     * Sets the file email content ID.
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value) : AbstractFileEmailContent
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the file email content ID.
     *
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Sets the path to the file to load structured content from.
     *
     * @param string $value
     * @return self
     */
    public function setStructuredContentPath(string $value) : AbstractFileEmailContent
    {
        $this->structuredContentPath = $value;

        return $this;
    }

    /**
     * Gets the path to the file to load structured content from.
     *
     * @return string;
     */
    public function getStructuredContentPath() : string
    {
        return $this->structuredContentPath;
    }

    /**
     * Gets the plain content.
     *
     * @NOTE this will generate empty string at the moment, unless overridden by child implementations {unfulvio 2021-09-02}
     *
     * @return string
     */
    public function getPlainContent(): string
    {
        return '';
    }

    /**
     * Gets contents from file.
     *
     * @return string
     */
    protected function getContentFromFile() : string
    {
        return file_get_contents($this->getStructuredContentPath()) ?: '';
    }
}
