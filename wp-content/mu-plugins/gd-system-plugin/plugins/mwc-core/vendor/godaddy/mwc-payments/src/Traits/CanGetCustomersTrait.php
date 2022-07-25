<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

/**
 * Can Get Customer Methods Trait.
 *
 * @since 0.1.0
 */
trait CanGetCustomersTrait
{
    use AdaptsCustomersTrait;

    /**
     * Performs customer method get request.
     *
     * @since 0.1.0
     *
     * @param array $params
     *
     * @return Customer
     * @throws Exception
     */
    public function get(array $params) : Customer
    {
        if (! method_exists($this, 'doRequest')) {
            throw new Exception('doRequest method is missing');
        }

        $response = $this->doRequest('GET', $params);

        return (new $this->customerAdapter($response->getBody()))->convertFromSource();
    }

    /**
     * Performs customer method get all request.
     *
     * @since 0.1.0
     *
     * @param array $params
     *
     * @return Customer[]
     * @throws Exception
     */
    public function getAll(array $params) : array
    {
        if (! method_exists($this, 'doRequest')) {
            throw new Exception('doRequest method is missing');
        }

        $response = $this->doRequest('GET', $params);
        $customerAdapter = $this->customerAdapter;

        return array_map(static function ($item) use ($customerAdapter) {
            return (new $customerAdapter($item))->convertFromSource();
        }, $response->getBody());
    }
}
