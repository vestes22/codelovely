<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

/**
 * Can Create Customers Trait.
 *
 * @since 0.1.0
 */
trait CanCreateCustomersTrait
{
    use AdaptsRequestsTrait;
    use AdaptsCustomersTrait;

    /**
     * Performs creates customer request.
     *
     * @since 0.1.0
     *
     * @param Customer $customer
     *
     * @return Customer
     * @throws Exception
     */
    public function create(Customer $customer) : Customer
    {
        return $this->doAdaptedRequest($customer, new $this->customerAdapter($customer));
    }
}
