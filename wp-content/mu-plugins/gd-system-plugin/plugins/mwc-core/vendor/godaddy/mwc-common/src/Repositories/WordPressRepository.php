<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Content\Context\Screen;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\WordPressScreenAdapter;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use WP;
use WP_Screen;

/**
 * WordPress repository handler.
 */
class WordPressRepository
{
    /**
     * Gets the main WordPress environment setup class.
     *
     * @return WP
     * @throws Exception
     */
    public static function getInstance() : WP
    {
        global $wp;

        if (! $wp || ! is_a($wp, 'WP')) {
            throw new Exception('WordPress environment not initialized.');
        }

        return $wp;
    }

    /**
     * Gets the plugin's assets URL.
     *
     * @param string $path optional path
     * @return string URL
     */
    public static function getAssetsUrl(string $path = '') : string
    {
        $config = Configuration::get('mwc.url');

        if (! $config) {
            return '';
        }

        $url = StringHelper::trailingSlash($config);

        return "{$url}assets/{$path}";
    }

    /**
     * Gets the current page.
     *
     * @return string|null
     */
    public static function getCurrentPage()
    {
        return ArrayHelper::get($GLOBALS, 'pagenow');
    }

    /**
     * Gets the WordPress Filesystem instance.
     *
     * @throws Exception
     */
    public static function getFilesystem()
    {
        if (! $wp_filesystem = ArrayHelper::get($GLOBALS, 'wp_filesystem')) {
            throw new Exception('Unable to connect to the WordPress filesystem -- wp_filesystem global not found');
        }

        if (is_a($wp_filesystem, 'WP_Filesystem_Base') && is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->has_errors()) {
            throw new Exception(sprintf('Unable to connect to the WordPress filesystem with error: %s', $wp_filesystem->errors->get_error_message()));
        }

        return $wp_filesystem;
    }

    /**
     * Gets the WP_User ID associated with the logged in user.
     *
     * @TODO remove this deprecated method {FN 2021-03-19}
     * @deprecated
     *
     * @return int|null
     * @throws Exception
     */
    public static function getCurrentUserId()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.4.1', User::class.'::getCurrent()');

        if ($user = static::getUser()) {
            return $user->ID;
        }

        return null;
    }

    /**
     * Gets the current {@see \WP_User}.
     *
     * @TODO remove this deprecated method {FN 2021-03-19}
     * @deprecated
     *
     * @return \WP_User|null
     */
    public static function getUser()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.4.1', User::class.'::getCurrent()');

        /** @NOTE this deprecated method was erroneously named as it would return the current user in reality, which in WordPress is always returned */
        $user = wp_get_current_user();

        return $user && $user->ID > 0 ? $user : null;
    }

    /**
     * Gets the {@see \WP_User} associated with a given email.
     *
     * @TODO remove this deprecated method {FN 2021-03-19}
     * @deprecated
     *
     * @param string $email the email to search for
     * @return \WP_User|null
     */
    public static function getUserByEmail(string $email)
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.4.1', User::class.'::getByEmail()');

        return get_user_by('email', $email) ?: null;
    }

    /**
     * Gets the {@see \WP_User} associated with a given login.
     *
     * @TODO remove this deprecated method {FN 2021-03-19}
     * @deprecated
     *
     * @param string $login the login to search for
     * @return \WP_User|null
     */
    public static function getUserByLogin(string $login)
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.4.1', User::class.'::getByHandle()');

        return get_user_by('login', $login) ?: null;
    }

    /**
     * Gets the {@see \WP_User} associated with a given id.
     *
     * @TODO remove this deprecated method {FN 2021-03-19}
     * @deprecated
     *
     * @param int $id the id to search for
     * @return \WP_User|null
     */
    public static function getUserById(int $id)
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '3.4.1', User::class.'::getById()');

        return get_user_by('id', $id) ?: null;
    }

    /**
     * Gets the current WordPress Version.
     *
     * @return string|null
     */
    public static function getVersion()
    {
        return Configuration::get('wordpress.version');
    }

    /**
     * Determines that a WordPress instance can be found.
     *
     * @return bool
     */
    public static function hasWordPressInstance() : bool
    {
        return (bool) Configuration::get('wordpress.absolute_path');
    }

    /**
     * Determines if a given value or values is the current page.
     *
     * @TODO add strict type casting when min version is PHP 8
     *
     * @param array|string $path path to check
     * @return bool
     */
    public static function isCurrentPage($path) : bool
    {
        $path = ArrayHelper::wrap($path);

        return ArrayHelper::contains($path, self::getCurrentPage())
            || ArrayHelper::contains($path, ArrayHelper::get($_GET, 'page', ''));
    }

    /**
     * Determines if the current instance is in CLI mode.
     *
     * @return bool
     */
    public static function isCliMode() : bool
    {
        return 'cli' === Configuration::get('mwc.mode');
    }

    /**
     * Determines whether WordPress is in debug mode.
     *
     * @return bool
     */
    public static function isDebugMode() : bool
    {
        return (bool) Configuration::get('wordpress.debug');
    }

    /**
     * Determines if the current request is for a WC REST API endpoint.
     *
     * @see WooCommerce::is_rest_api_request()
     *
     * @return bool
     */
    public static function isApiRequest() : bool
    {
        if (! $_SERVER['REQUEST_URI'] || ! function_exists('rest_get_url_prefix')) {
            return false;
        }

        $is_rest_api_request = StringHelper::contains($_SERVER['REQUEST_URI'], StringHelper::trailingSlash(rest_get_url_prefix()));

        /* applies WooCommerce core filter */
        return (bool) apply_filters('woocommerce_is_rest_api_request', $is_rest_api_request);
    }

    /**
     * Determines whether the current WordPress thread is a request for a WordPress admin page.
     *
     * @return bool
     */
    public static function isAdmin() : bool
    {
        return static::hasWordPressInstance() && is_admin();
    }

    /**
     * Determines whether the current WordPress thread is executing an AJAX callback.
     *
     * @return bool
     */
    public static function isAjax() : bool
    {
        return function_exists('wp_doing_ajax') && is_callable('wp_doing_ajax')
            ? wp_doing_ajax()
            : defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Requires the absolute path to the WordPress directory.
     *
     * @throws Exception
     */
    public static function requireWordPressInstance()
    {
        if (! self::hasWordPressInstance()) {
            // @TODO setting to throw an exception for now, may have to be revisited later (or possibly with a less generic exception) {FN 2020-12-18}
            throw new Exception('Unable to find the required WordPress instance');
        }
    }

    /**
     * Initializes and connect the WordPress Filesystem instance.
     *
     * Implementation adapted from {@see delete_plugins()}.
     *
     * @throws Exception
     */
    public static function requireWordPressFilesystem()
    {
        $base = Configuration::get('wordpress.absolute_path');

        require_once "{$base}wp-admin/includes/file.php";
        require_once "{$base}wp-admin/includes/plugin-install.php";
        require_once "{$base}wp-admin/includes/class-wp-upgrader.php";
        require_once "{$base}wp-admin/includes/plugin.php";

        // we are using an empty string as the value for the $form_post parameter because it is not relevant for our test.
        // If the function needs to show the form then the WordPress Filesystem is not currently configured for our needs.
        // We need to be able to access the filesystem without asking the user for credentials.
        ob_start();
        $credentials = request_filesystem_credentials('');
        ob_end_clean();

        if (false === $credentials || ! WP_Filesystem($credentials)) {
            static::getFilesystem();

            throw new Exception('Unable to connect to the WordPress filesystem');
        }
    }

    /**
     * Gets a Screen object using the data from the current WordPress screen object.
     *
     * @return Screen|null
     */
    public static function getCurrentScreen()
    {
        $currentWPScreen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (! $currentWPScreen instanceof WP_Screen) {
            return null;
        }

        return new Screen((new WordPressScreenAdapter($currentWPScreen))->convertFromSource());
    }

    /**
     * Gets WordPress instance current locale setting.
     *
     * @return string
     */
    public static function getLocale() : string
    {
        return Configuration::get('wordpress.locale');
    }

    /**
     * Gets a WP_Comment object given a comment ID.
     *
     * @param int $commentId
     * @return WP_Comment
     */
    public static function getComment(int $commentId)
    {
        return get_comment($commentId);
    }
}
