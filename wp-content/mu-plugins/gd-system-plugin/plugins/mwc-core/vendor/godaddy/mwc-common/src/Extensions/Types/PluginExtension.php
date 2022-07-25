<?php

namespace GoDaddy\WordPress\MWC\Common\Extensions\Types;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionActivationFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionDeactivationFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionInstallFailedException;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionUninstallFailedException;
use GoDaddy\WordPress\MWC\Common\Extensions\AbstractExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

/**
 * The plugin extension class.
 *
 * @since 1.0.0
 */
class PluginExtension extends AbstractExtension
{
    /** @var string asset type */
    const TYPE = 'plugin';

    /** @var string|null The plugin's basename, e.g. some-plugin/some-plugin.php */
    protected $basename;

    /** @var string|null the extension install path */
    protected $installPath;

    /** @var array key-value list of available icon URLs */
    protected $imageUrls = [];

    /**
     * Plugin constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->type = self::TYPE;
        $this->installPath = Configuration::get('wordpress.plugins_directory');
    }

    /**
     * Gets the plugin basename.
     *
     * e.g. woocommerce-plugin/woocommerce-plugin.php
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * Gets the plugin image URLs.
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
     * Gets the plugin install path.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getInstallPath()
    {
        return $this->installPath;
    }

    /**
     * Gets the currently installed plugin version or returns null.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getInstalledVersion()
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            // assume the plugin is not installed to avoid changing the contract of the method to start throwing an exception
            return;
        }

        if (! $this->isInstalled()) {
            return;
        }

        return ArrayHelper::get(get_plugin_data(StringHelper::trailingSlash($this->getInstallPath()).$this->getBasename()), 'Version');
    }

    /**
     * Gets the plugin name.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getName()
    {
        if ($this->name && StringHelper::startsWith($this->name, 'WooCommerce')) {
            return trim(StringHelper::after($this->name, 'WooCommerce'));
        }

        return $this->name;
    }

    /**
     * Sets the plugin basename.
     *
     * e.g. woocommerce-plugin/woocommerce-plugin.php
     *
     * @since 1.0.0
     *
     * @param string $value basename value to set
     * @return self
     */
    public function setBasename(string $value) : self
    {
        $this->basename = $value;

        return $this;
    }

    /**
     * Sets the plugin image URLs.
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
     * Activates the plugin.
     *
     * @since 1.0.0
     *
     * @returns void
     * @throws ExtensionActivationFailedException
     */
    public function activate()
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionActivationFailedException($exception->getMessage());
        }

        if (! $this->isInstalled()) {
            throw new ExtensionActivationFailedException(sprintf('Could not activate %s: the plugin is not installed.', $this->getName() ?? 'a plugin'));
        }

        $activated = activate_plugin($this->getBasename());

        if (is_a($activated, '\WP_Error', true)) {
            throw new ExtensionActivationFailedException($activated->get_error_message());
        }
    }

    /**
     * Determines whether the plugin is active.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function isActive() : bool
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            // assume the plugin is not active to avoid changing the contract of the method to start throwing an exception
            return false;
        }

        return (bool) is_plugin_active($this->getBasename());
    }

    /**
     * Deactivates the plugin.
     *
     * @since 1.0.0
     *
     * @throws ExtensionDeactivationFailedException
     */
    public function deactivate()
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionDeactivationFailedException($exception->getMessage());
        }

        deactivate_plugins($this->getBasename());

        if ($this->isActive()) {
            throw new ExtensionDeactivationFailedException(sprintf('%s was not deactivated successfully.', $this->getName() ?? 'A plugin'));
        }
    }

    /**
     * Installs the plugin.
     *
     * @since 1.0.0
     *
     * @throws ExtensionInstallFailedException
     */
    public function install()
    {
        try {
            $downloadable = $this->download();
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionInstallFailedException($exception->getMessage());
        }

        $result = unzip_file($downloadable, $this->installPath);

        unlink($downloadable);

        if (is_a($result, '\WP_Error')) {
            throw new ExtensionInstallFailedException($result->get_error_message());
        }

        if (! $this->isInstalled()) {
            throw new ExtensionInstallFailedException(sprintf('%s was not installed successfully.', $this->getName() ?? 'A plugin'));
        }

        // make sure to clear out plugins list cache after the plugin successfully installed
        wp_clean_plugins_cache();
    }

    /**
     * Determines if the plugin is installed.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function isInstalled() : bool
    {
        return $this->installPath && $this->getBasename() && is_readable(StringHelper::trailingSlash($this->installPath).$this->getBasename());
    }

    /**
     * Uninstall the Plugin.
     *
     * Implementation adapted from {@see wp_ajax_delete_plugin()}.
     *
     * @since 1.0.0
     *
     * @throws ExtensionDeactivationFailedException|ExtensionUninstallFailedException
     */
    public function uninstall()
    {
        if (! $this->isInstalled()) {
            return;
        }

        if ($this->isActive()) {
            $this->deactivate();
        }

        /* check filesystem credentials first because {@see delete_plugins()} will terminate the PHP process if credentials cannot be retrieved or are invalid */
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionUninstallFailedException($exception->getMessage());
        }

        $result = delete_plugins([$this->getBasename()]);

        if (is_a($result, '\WP_Error', true)) {
            throw new ExtensionUninstallFailedException($result->get_error_message());
        }

        if ($this->isInstalled()) {
            throw new ExtensionUninstallFailedException(sprintf('%s was not uninstalled successfully.', $this->getName() ?? 'A plugin'));
        }
    }
}
