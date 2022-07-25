<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters;

use DateTime;
use DateTimeZone;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\ExtensionAdapterContract;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * WooCommerce extension adapter.
 *
 * @since 1.0.0
 */
class WooCommerceExtensionAdapter implements ExtensionAdapterContract
{
    /** @var array source data */
    protected $data;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Converts from Data Source format.
     *
     * @since 1.0.0
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
     * @since 1.0.0
     *
     * @return array
     */
    private function getExtensionData(): array
    {
        return [
            'slug'                      => ArrayHelper::get($this->data, 'slug'),
            'name'                      => ArrayHelper::get($this->data, 'name'),
            'shortDescription'          => ArrayHelper::get($this->data, 'short_description'),
            'type'                      => $this->getType(),
            'version'                   => ArrayHelper::get($this->data, 'version'),
            'lastUpdated'               => $this->getLastUpdated(),
            'packageUrl'                => ArrayHelper::get($this->data, 'download_link'),
            'homepageUrl'               => ArrayHelper::get($this->data, 'homepage'),
            'documentationUrl'          => ArrayHelper::get($this->data, 'support_documentation'),
            'imageUrls'                 => $this->getImageUrls(),
        ];
    }

    /**
     * Gets data used for plugin extensions only.
     *
     * @since 1.0.0
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
     * Sometimes extensions have a non-standard basename so we need this helper method to ensure those are dealt with appropriately.
     * If more are discovered they should be added to the map array in this method.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    private function getPluginBasename()
    {
        if (! $slug = ArrayHelper::get($this->data, 'slug')) {
            return null;
        }

        $map = [
            // slug (dirname) => filename
            'woocommerce-product-enquiry-form' => 'product-enquiry-form',
        ];

        $filename = ArrayHelper::get($map, $slug, $slug);

        return "{$slug}/{$filename}.php";
    }

    /**
     * Converts to Data Source format.
     *
     * @since 1.0.0
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
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getType()
    {
        return ArrayHelper::get($this->data, 'type');
    }

    /**
     * Gets the UNIX timestamp representing when the extension was last updated.
     *
     * @since 1.0.0
     *
     * @return int|null
     */
    protected function getLastUpdated()
    {
        if (! ArrayHelper::get($this->data, 'last_updated')) {
            return null;
        }

        try {
            return (new DateTime(
                ArrayHelper::get($this->data, 'last_updated'),
                new DateTimeZone('UTC')
            ))->getTimestamp();
        } catch (Exception $e) {
            return null;
        }
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
        return ArrayHelper::where(ArrayHelper::wrap(ArrayHelper::get($this->data, 'icons')), function ($value) {
            return ! empty($value) && is_string($value);
        });
    }
}
