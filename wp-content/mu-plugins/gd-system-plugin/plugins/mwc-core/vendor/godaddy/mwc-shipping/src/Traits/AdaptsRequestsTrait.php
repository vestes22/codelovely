<?php

namespace GoDaddy\WordPress\MWC\Shipping\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Http\Request;

/**
 * Allows classes to adapt requests responses.
 *
 * @since 0.1.0
 */
trait AdaptsRequestsTrait
{
    /**
     * Preforms the request and returns the adapted response.
     *
     * @since 0.1.0
     *
     * @param DataSourceAdapterContract $adapter
     *
     * @return array|mixed
     *
     * @throws BaseException
     */
    public function doAdaptedRequest(DataSourceAdapterContract $adapter)
    {
        /** @var Request $request */
        $request = $adapter->convertFromSource();

        try {
            $response = $request->send();
        } catch (Exception $ex) {
            throw new BaseException($ex->getMessage());
        }

        if ($response->isError()) {
            throw new BaseException($response->getErrorMessage());
        }

        return $adapter->convertToSource($response);
    }
}
