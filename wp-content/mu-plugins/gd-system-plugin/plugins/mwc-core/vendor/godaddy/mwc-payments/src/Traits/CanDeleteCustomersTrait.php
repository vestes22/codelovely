<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;

/**
 * Can Delete Customers Trait.
 *
 * @since 0.1.0
 */
trait CanDeleteCustomersTrait
{
    use AdaptsRequestsTrait;
    use AdaptsCustomersTrait;

    /**
     * Performs delete customer request.
     *
     * @since 0.1.0
     *
     * @param Customer $customer
     *
     * @return Customer
     * @throws Exception
     */
    public function delete(Customer $customer) : Customer
    {
        return $this->doAdaptedRequest($customer, new $this->customerAdapter($customer));
    }
}
