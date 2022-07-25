<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;

/**
 * Requests Adapter trait.
 *
 * @since 0.1.0
 */
trait AdaptsRequestsTrait
{
    /**
     * Performs the request with the adapted body data.
     *
     * @since 0.1.0
     *
     * @param mixed $subject
     * @param DataSourceAdapterContract $adapter
     *
     * @return mixed
     * @throws Exception
     */
    abstract public function doAdaptedRequest($subject, DataSourceAdapterContract $adapter);
}
