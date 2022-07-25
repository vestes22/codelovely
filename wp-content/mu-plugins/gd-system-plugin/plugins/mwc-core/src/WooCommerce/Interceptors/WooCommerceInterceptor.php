<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Interceptors\Contracts\InterceptorContract;

/**
 * The WooCommerceInterceptor class instantiates InterceptorContract instances for hooking into actions and filters.
 */
class WooCommerceInterceptor
{
    /** @var string[] list of class names that implement InterceptorContract */
    protected $interceptors = [
        CouponInterceptor::class,
        CustomerInterceptor::class,
    ];

    /**
     * WooCommerceInterceptor constructor.
     *
     * @throws BaseException
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Adds all configured interceptors hooks.
     *
     * @throws BaseException
     */
    protected function addHooks()
    {
        foreach ($this->interceptors as $interceptorClassName) {
            $this->instantiateInterceptor($interceptorClassName)->addHooks();
        }
    }

    /**
     * Instantiates an interceptor.
     *
     * Exceptions may be thrown for invalid interceptors.
     *
     * @param string $interceptorClassName
     * @return InterceptorContract
     * @throws BaseException
     */
    protected function instantiateInterceptor(string $interceptorClassName) : InterceptorContract
    {
        if (! class_exists($interceptorClassName)) {
            throw new BaseException("$interceptorClassName is not a valid class.");
        }

        return new $interceptorClassName();
    }
}
