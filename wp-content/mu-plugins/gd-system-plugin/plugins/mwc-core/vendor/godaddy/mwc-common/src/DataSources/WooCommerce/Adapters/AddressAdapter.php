<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WooCommerce\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Address;

/**
 * Address adapter.
 *
 * @since 3.4.1
 */
class AddressAdapter implements DataSourceAdapterContract
{
    /** @var array address source */
    private $source;

    /**
     * Address adapter constructor.
     *
     * @since 3.4.1
     *
     * @param array $address
     */
    public function __construct(array $address)
    {
        $this->source = $address;
    }

    /**
     * Converts a WooCommerce address into a native object.
     *
     * @since 3.4.1
     *
     * @return Address
     */
    public function convertFromSource() : Address
    {
        return (new Address())
            ->setBusinessName(ArrayHelper::get($this->source, 'company', ''))
            ->setFirstname(ArrayHelper::get($this->source, 'first_name', ''))
            ->setLastname(ArrayHelper::get($this->source, 'last_name', ''))
            ->setLines(array_filter([ArrayHelper::get($this->source, 'address_1', ''), ArrayHelper::get($this->source, 'address_2', '')]))
            ->setLocality(ArrayHelper::get($this->source, 'city', ''))
            ->setAdministrativeDistricts((array) ArrayHelper::get($this->source, 'state', []))
            ->setPostalCode(ArrayHelper::get($this->source, 'postcode', ''))
            ->setCountryCode(ArrayHelper::get($this->source, 'country', ''))
            ->setPhone(ArrayHelper::get($this->source, 'phone', ''));
    }

    /**
     * Converts a native address into a WooCommerce address.
     *
     * @since 3.4.1
     *
     * @param Address|null $address
     * @return array
     */
    public function convertToSource($address = null) : array
    {
        if (! $address instanceof Address) {
            return $this->source;
        }

        $lines = $address->getLines();
        $districts = $address->getAdministrativeDistricts();

        $this->source = [
            'company'    => $address->getBusinessName(),
            'first_name' => $address->getFirstName(),
            'last_name'  => $address->getLastName(),
            'address_1'  => $lines[0] ?? '',
            'address_2'  => $lines[1] ?? '',
            'city'       => $address->getLocality(),
            'state'      => $districts[0] ?? '',
            'postcode'   => $address->getPostalCode(),
            'country'    => $address->getCountryCode(),
            'phone'      => $address->getPhone(),
        ];

        return $this->source;
    }
}
