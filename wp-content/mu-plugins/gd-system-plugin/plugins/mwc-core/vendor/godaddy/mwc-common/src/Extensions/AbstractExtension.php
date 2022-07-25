<?php

namespace GoDaddy\WordPress\MWC\Common\Extensions;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\ExtensionDownloadFailedException;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;

/**
 * Abstract extension class.
 *
 * Represents an extension to the WordPress base, such as a plugin or theme.
 *
 * @since 1.0.0
 */
abstract class AbstractExtension
{
    use CanBulkAssignPropertiesTrait;
    use CanConvertToArrayTrait;

    /** @var string|null the ID, if any */
    protected $id;

    /** @var string|null the slug */
    protected $slug;

    /** @var string|null the name */
    protected $name;

    /** @var string|null the short description */
    protected $shortDescription;

    /** @var string|null the extension type */
    protected $type;

    /** @var string|null the slug of an assigned category, if any */
    protected $category;

    /** @var string|null the extension's brand */
    protected $brand;

    /** @var string|null the version number */
    protected $version;

    /** @var string|null the UNIX timestamp representing when the extension was last updated */
    protected $lastUpdated;

    /** @var string|null the minimum version of PHP required to run the extension */
    protected $minimumPhpVersion;

    /** @var string|null the minimum version of WordPress required to run the extension */
    protected $minimumWordPressVersion;

    /** @var string|null the minimum version of WooCommerce required to run the extension */
    protected $minimumWooCommerceVersion;

    /** @var string|null the URL to download the extension package */
    protected $packageUrl;

    /** @var string|null the URL for the extension's homepage */
    protected $homepageUrl;

    /** @var string|null the URL for the extension's documentation */
    protected $documentationUrl;

    /**
     * Gets the ID.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the slug.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Gets the name.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the short description.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    /**
     * Gets the type.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the category.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Gets the brand.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Gets the version.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Gets the timestamp representing when the asset was last updated.
     *
     * @since 1.0.0
     *
     * @return int|null
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    /**
     * Gets the minimum required PHP version to use this asset.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getMinimumPHPVersion()
    {
        return $this->minimumPhpVersion;
    }

    /**
     * Gets the minimum required WordPress version to use this asset.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getMinimumWordPressVersion()
    {
        return $this->minimumWordPressVersion;
    }

    /**
     * Gets the minimum required WooCommerce version to use this asset.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getMinimumWooCommerceVersion()
    {
        return $this->minimumWooCommerceVersion;
    }

    /**
     * Gets the package URL.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getPackageUrl()
    {
        return $this->packageUrl;
    }

    /**
     * Gets the homepage URL.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getHomepageUrl()
    {
        return $this->homepageUrl;
    }

    /**
     * Gets the documentation URL.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getDocumentationUrl()
    {
        return $this->documentationUrl;
    }

    /**
     * Sets the ID.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setId(string $value) : self
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the slug.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setSlug(string $value) : self
    {
        $this->slug = $value;

        return $this;
    }

    /**
     * Sets the name.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setName(string $value) : self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Sets the short description.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setShortDescription(string $value) : self
    {
        $this->shortDescription = $value;

        return $this;
    }

    /**
     * Sets the type.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setType(string $value) : self
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Sets the category.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setCategory(string $value) : self
    {
        $this->category = $value;

        return $this;
    }

    /**
     * Sets the brand.
     *
     * @since 3.4.1
     *
     * @param string $brand value to set
     * @return self
     */
    public function setBrand(string $brand) : self
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Sets the version.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setVersion(string $value) : self
    {
        $this->version = $value;

        return $this;
    }

    /**
     * Sets the time the asset was last updated.
     *
     * @since 1.0.0
     *
     * @param int $value value to set, as a UTC timestamp
     * @return self
     */
    public function setLastUpdated(int $value) : self
    {
        $this->lastUpdated = $value;

        return $this;
    }

    /**
     * Sets the minimum PHP version required to use this asset.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setMinimumPHPVersion(string $value) : self
    {
        $this->minimumPhpVersion = $value;

        return $this;
    }

    /**
     * Sets the minimum WordPress version required to use this asset.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setMinimumWordPressVersion(string $value) : self
    {
        $this->minimumWordPressVersion = $value;

        return $this;
    }

    /**
     * Sets the minimum WooCommerce version required to use this asset.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setMinimumWooCommerceVersion(string $value) : self
    {
        $this->minimumWooCommerceVersion = $value;

        return $this;
    }

    /**
     * Sets the package URL.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setPackageUrl(string $value) : self
    {
        $this->packageUrl = $value;

        return $this;
    }

    /**
     * Sets the homepage URL.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setHomepageUrl(string $value) : self
    {
        $this->homepageUrl = $value;

        return $this;
    }

    /**
     * Sets the documentation URL.
     *
     * @since 1.0.0
     *
     * @param string $value value to set
     * @return self
     */
    public function setDocumentationUrl(string $value) : self
    {
        $this->documentationUrl = $value;

        return $this;
    }

    /**
     * Downloads the extension.
     *
     * @NOTE Methods calling this function need to {@see unlink()} the temporary file returned by {@see download_url()}.
     *
     * @since 1.0.0
     *
     * @return string temporary filename
     * @throws ExtensionDownloadFailedException
     */
    public function download() : string
    {
        try {
            WordPressRepository::requireWordPressFilesystem();
        } catch (Exception $exception) {
            throw new ExtensionDownloadFailedException($exception->getMessage());
        }

        $downloadable = download_url($this->getPackageUrl());

        if (is_a($downloadable, '\WP_Error', true)) {
            throw new ExtensionDownloadFailedException($downloadable->get_error_message());
        }

        return $downloadable;
    }

    /**
     * Activates the extension.
     *
     * @since 1.0.0
     */
    abstract public function activate();

    /**
     * Determines whether the extension is active.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    abstract public function isActive() : bool;

    /**
     * Deactivates the extension.
     *
     * @since 1.0.0
     */
    abstract public function deactivate();

    /**
     * Installs the extension.
     *
     * @since 1.0.0
     */
    abstract public function install();

    /**
     * Determines if the extension is installed.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    abstract public function isInstalled() : bool;

    /**
     * Uninstalls the Extension.
     *
     * @since 1.0.0
     */
    abstract public function uninstall();
}
