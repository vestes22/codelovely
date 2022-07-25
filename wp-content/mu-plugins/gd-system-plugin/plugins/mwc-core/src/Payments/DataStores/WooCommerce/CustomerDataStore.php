<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts\DataStoreContract;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use WC_Customer;

/**
 * WooCommerce Customer datastore class.
 *
 * @since 2.10.0
 */
class CustomerDataStore implements DataStoreContract
{
    /** @var string data provider class name */
    protected $providerName;

    /** @var string customer data adapter class name, defaults to {@see CustomerAdapter::class} */
    protected $customerAdapter;

    /**
     * Customer data store constructor.
     *
     * @since 2.10.0
     *
     * @param string $providerName
     * @param string $customerAdapter, optional, defaults to {@see CustomerAdapter::class}
     */
    public function __construct(string $providerName, string $customerAdapter = CustomerAdapter::class)
    {
        $this->providerName = $providerName;
        $this->customerAdapter = $customerAdapter;
    }

    /**
     * Returns WooCommerce customer object of the given ID.
     *
     * @since z.y.z
     *
     * @param int $id
     *
     * @return WC_Customer
     * @throws BaseException
     */
    protected function getWooCustomer(int $id) : WC_Customer
    {
        try {
            $wooCustomer = new WC_Customer($id);
        } catch (Exception $ex) {
            throw new BaseException($ex->getMessage());
        }

        return $wooCustomer;
    }

    /**
     * Deletes customer data from the data store.
     *
     * @param int $id
     * @return bool
     * @throws BaseException
     * @throws Exception
     */
    public function delete(int $id = null) : bool
    {
        if (null === $id) {
            throw new BaseException('Customer ID is missing.');
        }

        $wooCustomer = $this->getWooCustomer($id);
        $wooCustomer->delete_meta_data("_{$this->providerName}_remoteId");
        $wooCustomer->save_meta_data();

        return true;
    }

    /**
     * Reads customer data from the data store.
     *
     * @since 2.10.0
     *
     * @param int $id
     * @return Customer
     * @throws Exception
     */
    public function read(int $id = null) : Customer
    {
        if (null === $id) {
            throw new Exception('Customer ID is missing.');
        }

        $wooCustomer = $this->getWooCustomer($id);

        /** @var Customer $customer */
        $customer = (new $this->customerAdapter($wooCustomer))->convertFromSource();

        return $customer->setRemoteId($wooCustomer->get_meta("_{$this->providerName}_remoteId"));
    }

    /**
     * Saves customer data to the data store.
     *
     * @since 2.10.0
     *
     * @param Customer $customer
     * @return Customer
     * @throws Exception
     */
    public function save(Customer $customer = null) : Customer
    {
        if (null === $customer) {
            throw new Exception('Customer object is missing');
        }

        /** @var WC_Customer $wooCustomer */
        $wooCustomer = (new $this->customerAdapter(new WC_Customer()))->convertToSource($customer);

        $wooCustomer->update_meta_data("_{$this->providerName}_remoteId", $customer->getRemoteId());
        $wooCustomer->save();

        return $customer;
    }
}
