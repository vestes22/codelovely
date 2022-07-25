<?php

namespace GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters\AddressAdapter;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use WC_Customer;

/**
 * Customer adapter.
 *
 * @since 0.1.0
 */
class CustomerAdapter
{
    /** @var WC_Customer WooCommerce customer object */
    private $source;

    /**
     * Customer adapter constructor.
     *
     * @since 0.1.0
     *
     * @param WC_Customer $wooCommerceCustomer WooCommerce customer object
     */
    public function __construct(WC_Customer $wooCommerceCustomer)
    {
        $this->source = $wooCommerceCustomer;
    }

    /**
     * Converts a WooCommerce customer into a native customer.
     *
     * @since 0.1.0
     *
     * @return Customer
     */
    public function convertFromSource() : Customer
    {
        $customer = new Customer();
        $customer->setId($this->source->get_id());
        $customer->setRemoteId($this->source->get_meta('mwc_remote_id'));
        $customer->setShippingAddress((new AddressAdapter($this->source->get_shipping()))->convertFromSource());
        $customer->setBillingAddress((new AddressAdapter($this->source->get_billing()))->convertFromSource());

        if ($user = User::get($this->source->get_id())) {
            $customer->setUser($user);
        }

        return $customer;
    }

    /**
     * Converts a native payment method into a WooCommerce token.
     *
     * @since 0.1.0
     *
     * @param Customer $customer
     *
     * @return WC_Customer
     */
    public function convertToSource(Customer $customer) : WC_Customer
    {
        $this->source->set_id($customer->getId());
        $this->source->update_meta_data('mwc_remote_id', $customer->getRemoteId());

        if ($user = $customer->getUser()) {
            $this->source->set_username($customer->getUser()->getHandle());
            $this->source->set_email($customer->getUser()->getEmail());
            $this->source->set_first_name($customer->getUser()->getFirstName());
            $this->source->set_last_name($customer->getUser()->getLastName());
            $this->source->set_display_name($customer->getUser()->getDisplayName());
        }

        if ($shippingAddress = $customer->getShippingAddress()) {
            $adaptedShippingAddress = (new AddressAdapter([]))->convertToSource($customer->getShippingAddress());
            $this->source->set_shipping_company($adaptedShippingAddress['company']);
            $this->source->set_shipping_first_name($adaptedShippingAddress['first_name']);
            $this->source->set_shipping_last_name($adaptedShippingAddress['last_name']);
            $this->source->set_shipping_address_1($adaptedShippingAddress['address_1']);
            $this->source->set_shipping_address_2($adaptedShippingAddress['address_2']);
            $this->source->set_shipping_city($adaptedShippingAddress['city']);
            $this->source->set_shipping_state($adaptedShippingAddress['state']);
            $this->source->set_shipping_postcode($adaptedShippingAddress['postcode']);
            $this->source->set_shipping_country($adaptedShippingAddress['country']);
        }

        if ($billingAddress = $customer->getBillingAddress()) {
            $adaptedBillingAddress = (new AddressAdapter([]))->convertToSource($customer->getBillingAddress());
            $this->source->set_billing_company($adaptedBillingAddress['company']);
            $this->source->set_billing_first_name($adaptedBillingAddress['first_name']);
            $this->source->set_billing_last_name($adaptedBillingAddress['last_name']);
            $this->source->set_billing_address_1($adaptedBillingAddress['address_1']);
            $this->source->set_billing_address_2($adaptedBillingAddress['address_2']);
            $this->source->set_billing_city($adaptedBillingAddress['city']);
            $this->source->set_billing_state($adaptedBillingAddress['state']);
            $this->source->set_billing_postcode($adaptedBillingAddress['postcode']);
            $this->source->set_billing_country($adaptedBillingAddress['country']);
        }

        return $this->source;
    }
}
