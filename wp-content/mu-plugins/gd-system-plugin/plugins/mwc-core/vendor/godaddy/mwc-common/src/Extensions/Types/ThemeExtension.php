<?php

namespace GoDaddy\WordPress\MWC\Common\Extensions\Types;

use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;

/**
 * The Theme extension class.
 *
 * @since 1.0.0
 */
class ThemeExtension extends AbstractExtension
{
    /** @var string asset type */
    const TYPE = 'theme';

    /** @var array key-value list of available icon URLs */
    protected $imageUrls = [];

    /**
     * Theme constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->type = self::TYPE;
    }

    /**
     * Gets the image URLs.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getImageUrls() : array
    {
        return $this->imageUrls;
    }

    /**
     * Gets the currently installed version or returns null.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getInstalledVersion()
    {
        // @TODO implement this method {JO 2021-02-12}
    }

    /**
     * Sets the image URLs.
     *
     * @since 1.0.0
     *
     * @param string[] $urls URLs to set
     *
     * @return self
     */
    public function setImageUrls(array $urls) : self
    {
        $this->imageUrls = $urls;

        return $this;
    }

    /**
     * Activates the theme.
     *
     * @since 1.0.0
     */
    public function activate()
    {
        // @TODO implement this method {FN 2021-01-12}
    }

    /**
     * Determines whether the theme is active.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function isActive() : bool
    {
        // @TODO implement this method {FN 2021-01-12}
        return false;
    }

    /**
     * Deactivates the theme.
     *
     * @since 1.0.0
     */
    public function deactivate()
    {
        // @TODO implement this method {FN 2021-01-12}
    }

    /**
     * Installs the theme.
     *
     * @since 1.0.0
     */
    public function install()
    {
        // @TODO implement this method {FN 2021-01-12}
    }

    /**
     * Determines if the theme is installed.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        // @TODO implement this method {FN 2021-01-12}
        return false;
    }

    /**
     * Uninstall the Plugin.
     *
     * @since 1.0.0
     */
    public function uninstall()
    {
        // @TODO implement this method {JO 2021-02-12}
    }
}
