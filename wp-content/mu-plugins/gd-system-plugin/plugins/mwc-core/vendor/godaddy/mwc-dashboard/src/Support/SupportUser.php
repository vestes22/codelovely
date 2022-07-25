<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Support;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;

class SupportUser
{
    /**
     * Creates a support user and gives it admin rights.
     *
     * @return \WP_User|null
     * @throws Exception
     */
    public static function create()
    {
        $supportUserId = wp_create_user(Configuration::get('support.support_user.login'), wp_generate_password(), Configuration::get('support.support_user.email'));

        if (! is_wp_error($supportUserId)) {
            if ($supportUser = get_user_by('id', $supportUserId)) {
                $supportUser->add_role('administrator');

                return $supportUser;
            }
        }

        return null;
    }
}
