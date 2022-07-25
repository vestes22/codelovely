<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\Contracts\InterceptorContract;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CustomerAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\Customer;
use WC_Customer;

/**
 * A WooCommerce interceptor to hook on customer actions and filters.
 */
class CustomerInterceptor implements InterceptorContract
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::action()
                ->setGroup('user_register')
                ->setHandler([$this, 'onWordPressUserRegister'])
                ->execute();

        Register::action()
                ->setGroup('profile_update')
                ->setHandler([$this, 'onWordPressUserProfileUpdate'])
                ->execute();
    }

    /**
     * Calls the native customer model method to save the new customer when a WP_User with role "customer" is added.
     *
     * @param int $userId the user ID
     */
    public function onWordPressUserRegister($userId)
    {
        if (($wpUser = get_user_by('id', $userId)) &&
            ArrayHelper::contains((array) $wpUser->roles, 'customer') &&
            $wcCustomer = new WC_Customer($userId)) {
            $customer = $this->getConvertedCustomer($wcCustomer);
            $customer->save();
        }
    }

    /**
     * Calls the native customer model method to update the customer when the profile of a WP_User with role "customer" is updated.
     *
     * @param int $userId the user ID
     */
    public function onWordPressUserProfileUpdate($userId)
    {
        if (($wpUser = get_user_by('id', $userId)) &&
            ArrayHelper::contains((array) $wpUser->roles, 'customer') &&
            $wcCustomer = new WC_Customer($userId)) {
            $customer = $this->getConvertedCustomer($wcCustomer);
            $customer->update();
        }
    }

    /**
     * Converts a WooCommerce customer object into a native customer object.
     *
     * @param WC_Customer $customer
     * @return Customer
     * @throws Exception
     */
    protected function getConvertedCustomer(WC_Customer $customer) : Customer
    {
        return (new CustomerAdapter($customer))->convertFromSource();
    }
}
