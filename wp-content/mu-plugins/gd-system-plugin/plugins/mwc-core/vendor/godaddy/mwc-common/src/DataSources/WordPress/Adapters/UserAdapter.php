<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;

/**
 * Adapter to convert between a WordPress user object and a native user object.
 *
 * @since 3.4.1
 */
class UserAdapter implements DataSourceAdapterContract
{
    /** @var array user data */
    private $data;

    /**
     * WordPress user adapter constructor.
     *
     * @since 3.4.1
     *
     * @param array|User|\WP_User $data user data from WP_User, User, or array of data
     */
    public function __construct($data)
    {
        if (is_a($data, 'WP_User', true)) {
            /* @var \WP_User $data some keys may not be available in the {@see \WP_User} object's array form using to array method here */
            $this->data = array_merge($data->to_array(), [
                'user_firstname' => $data->user_firstname ?? '',
                'user_lastname' => $data->user_lastname ?? '',
                'nickname' => $data->nickname ?? '',
            ]);
        } elseif ($data instanceof User) {
            $this->data = $data->toArray();
        } else {
            $this->data = (array) $data;
        }
    }

    /**
     * Converts native user data to WordPress user data.
     *
     * @since 3.4.1
     *
     * @return array
     */
    public function convertToSource() : array
    {
        return [
            'ID'             => ArrayHelper::get($this->data, 'id', 0),
            'user_email'     => ArrayHelper::get($this->data, 'email', ''),
            'user_login'     => ArrayHelper::get($this->data, 'handle', ''),
            'user_firstname' => ArrayHelper::get($this->data, 'firstName', ''),
            'user_lastname'  => ArrayHelper::get($this->data, 'lastName', ''),
            'nickname'       => ArrayHelper::get($this->data, 'nickname', ''),
            'user_nicename'  => ArrayHelper::get($this->data, 'displayName', ''),
        ];
    }

    /**
     * Converts WordPress user data to native user data.
     *
     * @since 3.4.1
     *
     * @return array
     */
    public function convertFromSource() : array
    {
        return [
            'id'          => ArrayHelper::get($this->data, 'ID', 0),
            'email'       => ArrayHelper::get($this->data, 'user_email', ''),
            'handle'      => ArrayHelper::get($this->data, 'user_login', ''),
            'firstName'   => ArrayHelper::get($this->data, 'user_firstname', ''),
            'lastName'    => ArrayHelper::get($this->data, 'user_lastname', ''),
            'nickname'    => ArrayHelper::get($this->data, 'nickname', ''),
            'displayName' => ArrayHelper::get($this->data, 'user_nicename', ''),
        ];
    }
}
