<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\Contracts;

/**
 * Extension adapter contract.
 *
 * @since 1.0.0
 */
interface ExtensionAdapterContract extends DataSourceAdapterContract
{
    /**
     * Gets the type of the extension.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getType();

    /**
     * Gets the image URLs.
     *
     * @since 1.0.0
     *
     * @return string[]
     */
    public function getImageUrls() : array;
}
