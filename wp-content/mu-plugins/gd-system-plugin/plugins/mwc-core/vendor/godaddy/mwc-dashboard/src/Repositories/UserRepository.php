<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Repositories;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Dashboard\Message\MessagesOptedIn;

/**
 * User repository handler.
 */
class UserRepository
{
    /**
     * Returns the user full name (if set) or login.
     *
     * @deprecated x.y.z
     *
     * @param \WP_User|null $wpUser
     * @return string
     */
    public static function getUserName(\WP_User $wpUser = null) : string
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, 'x.y.z', User::class.'::getFullName()');

        if (! empty($wpUser) && $wpUser instanceof \WP_User) {
            $user = User::getById($wpUser->ID);
        } else {
            $user = User::getCurrent();
        }

        $name = $user->getFullName();

        if (empty($name)) {
            // fallback to login, if first and last name are not set
            $name = $user->getHandle();
        }

        return $name;
    }

    /**
     * Returns the password reset URL for the given user.
     *
     * @deprecated x.y.z
     *
     * @param \WP_User|null $user
     * @return string
     * @throws BaseException
     */
    public static function getPasswordResetUrl(\WP_User $wpUser = null): string
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, 'x.y.z', User::class.'::getPasswordResetUrl()');

        if (! empty($wpUser) && $wpUser instanceof \WP_User) {
            $user = User::getById($wpUser->ID);
        } else {
            $user = User::getCurrent();
        }

        return $user->getPasswordResetUrl();
    }

    /**
     * Checks if the user has opted in to receive MWC Dashboard messages.
     *
     * @return bool
     */
    public static function userOptedInForDashboardMessages(): bool
    {
        if (empty($currentUser = User::getCurrent())) {
            return false;
        }

        return (bool) (new MessagesOptedIn($currentUser->getId()))->getUserMeta();
    }
}
