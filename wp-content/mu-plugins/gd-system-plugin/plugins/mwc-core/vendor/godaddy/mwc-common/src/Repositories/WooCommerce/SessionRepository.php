<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use WC_Session;

/**
 * A repository for handling the WooCommerce session.
 */
class SessionRepository
{
    /**
     * Gets the WooCommerce countries handler instance.
     *
     * @return WC_Session
     * @throws Exception
     */
    public static function getInstance() : WC_Session
    {
        $wc = WooCommerceRepository::getInstance();

        if (! $wc || empty($wc->session) || ! $wc->session instanceof WC_Session) {
            throw new Exception(__('WooCommerce session is not available', 'mwc-core'));
        }

        return $wc->session;
    }

    /**
     * Gets a session value for a given key.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     * @throws Exception
     */
    public static function get(string $key, $default = null)
    {
        return static::getInstance()->get($key, $default);
    }

    /**
     * Sets a value to session with a given key.
     *
     * @param string $key
     * @param mixed $value
     * @return array|string
     * @throws Exception
     */
    public static function set(string $key, $value) : WC_Session
    {
        static::getInstance()->set($key, $value);

        return static::getInstance();
    }
}
