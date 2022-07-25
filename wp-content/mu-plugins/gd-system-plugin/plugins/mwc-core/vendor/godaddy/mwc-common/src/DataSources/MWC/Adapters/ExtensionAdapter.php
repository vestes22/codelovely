<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\MWC\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\ExtensionAdapterContract;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * The extension adapter.
 *
 * @since 3.4.1
 */
class ExtensionAdapter implements ExtensionAdapterContract
{
    /** @var array source data */
    protected $data;

    /**
     * Constructor.
     *
     * @since 3.4.1
     *
     * @param array $data data to be converted
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Converts from Data Source format.
     *
     * @since 3.4.1
     *
     * @return array
     * @throws Exception
     */
    public function convertFromSource() : array
    {
        $data = $this->getExtensionData();

        if (PluginExtension::TYPE === $this->getType()) {
            $data = ArrayHelper::combine($data, $this->getPluginData());
        }

        return ArrayHelper::where($data, function ($value) {
            return ! is_null($value);
        });
    }

    /**
     * Gets common data for extensions.
     *
     * @since 3.4.1
     *
     * @return array
     */
    private function getExtensionData(): array
    {
        return [
            'id'                        => ArrayHelper::get($this->data, 'extensionId'),
            'slug'                      => ArrayHelper::get($this->data, 'slug'),
            'name'                      => ArrayHelper::get($this->data, 'label'),
            'shortDescription'          => ArrayHelper::get($this->data, 'shortDescription'),
            'type'                      => $this->getType(),
            'category'                  => ArrayHelper::get($this->data, 'category'),
            'version'                   => ArrayHelper::get($this->data, 'version.version'),
            'lastUpdated'               => strtotime(ArrayHelper::get($this->data, 'version.releasedAt', '')) ?: null,
            'minimumPhpVersion'         => ArrayHelper::get($this->data, 'version.minimumPhpVersion'),
            'minimumWordPressVersion'   => ArrayHelper::get($this->data, 'version.minimumWordPressVersion'),
            'minimumWooCommerceVersion' => ArrayHelper::get($this->data, 'version.minimumWooCommerceVersion'),
            'packageUrl'                => ArrayHelper::get($this->data, 'version.links.package.href'),
            'homepageUrl'               => ArrayHelper::get($this->data, 'links.homepage.href'),
            'documentationUrl'          => ArrayHelper::get($this->data, 'links.documentation.href'),
            'imageUrls'                 => $this->getImageUrls(),
            'brand'                     => strtolower(! empty($brand = ArrayHelper::get($this->data, 'brand')) ? $brand : 'godaddy'),
        ];
    }

    /**
     * Gets data used for plugin extensions only.
     *
     * @since 3.4.1
     *
     * @return array
     */
    private function getPluginData(): array
    {
        return [
            'basename' => $this->getPluginBasename(),
        ];
    }

    /**
     * Gets the WooCommerce plugin basename from its slug.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    private function getPluginBasename()
    {
        if (! $slug = ArrayHelper::get($this->data, 'slug')) {
            return null;
        }

        return "{$slug}/{$slug}.php";
    }

    /**
     * Converts to Data Source format.
     *
     * @since 3.4.1
     *
     * @return array
     */
    public function convertToSource() : array
    {
        return $this->data;
    }

    /**
     * Gets the type of the extension.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    public function getType()
    {
        return strtolower(ArrayHelper::get($this->data, 'type', PluginExtension::TYPE));
    }

    /**
     * Gets the image URLs.
     *
     * @since 3.4.1
     *
     * @return array
     */
    public function getImageUrls() : array
    {
        return ArrayHelper::where(ArrayHelper::wrap(ArrayHelper::get($this->data, 'imageUrls')), function ($value) {
            return ! empty($value) && is_string($value);
        });
    }
}
