<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters\UserAdapter;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;

/**
 * Native user object.
 *
 * @since 3.4.1
 */
class User extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var string display name */
    protected $displayName;

    /** @var string email address */
    protected $email;

    /** @var string first name */
    protected $firstName;

    /** @var string login handle */
    protected $handle;

    /** @var int unique ID */
    protected $id;

    /** @var string last name */
    protected $lastName;

    /** @var string nickname */
    protected $nickname;

    /**
     * Gets the user display name.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Gets the user email.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Gets the user first name.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Gets the full name, if available.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getFullName() : string
    {
        if (! ($first = $this->getFirstName()) || ! ($last = $this->getLastName())) {
            return $this->getDisplayName() ?? '';
        }

        // @TODO some locales invert the position of the first and the last name and we might have to account for this in the future, maybe with a method argument? {FN 2021-03-19}
        return "{$first} {$last}";
    }

    /**
     * Gets the user handle.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Gets the user ID.
     *
     * @since 3.4.1
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the user first name.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Gets the user nickname.
     *
     * @since 3.4.1
     *
     * @return string|null
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * Gets the user password rest URL.
     *
     * @since 3.4.1
     *
     * @return string
     * @throws Exception
     */
    public function getPasswordResetUrl() : string
    {
        $passwordResetKey = get_password_reset_key(get_user_by('id', $this->id));

        if (is_a($passwordResetKey, 'WP_Error', true)) {
            // @TODO: Update to specific exception after deciding the folder location of where that should live {JO: 2021-03-26}
            throw new SentryException($passwordResetKey->get_error_message());
        }

        $parameters = ArrayHelper::query([
            'action' => 'rp',
            'key' => $passwordResetKey,
            'login' => rawurlencode($this->getHandle()),
        ]);

        // WordPress may filter this potentially to a non-string, so we ensure the type is the expected one
        // @TODO: Should really move the site url to a config given its useful in many places -- though maybe its a security issue since it can be overwritten {JO: 2021-03-26}
        $url = network_site_url("wp-login.php?{$parameters}", 'login');

        return is_string($url) ? $url : '';
    }

    /**
     * Sets the user email.
     *
     * @since 3.4.1
     *
     * @param string $email
     * @return self
     */
    public function setEmail(string $email) : self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Sets the user display name.
     *
     * @since 3.4.1
     *
     * @param string $displayName
     * @return self
     */
    public function setDisplayName(string $displayName) : self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Sets the user first name.
     *
     * @since 3.4.1
     *
     * @param string $firstName
     * @return self
     */
    public function setFirstName(string $firstName) : self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Sets the user login handle.
     *
     * @since 3.4.1
     *
     * @param string $handle
     * @return self
     */
    public function setHandle(string $handle) : self
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * Sets the user ID.
     *
     * @since 3.4.1
     *
     * @param int $id
     * @return self
     */
    public function setId(int $id) : self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets the user last name.
     *
     * @since 3.4.1
     *
     * @param string $lastName
     * @return self
     */
    public function setLastName(string $lastName) : self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Sets the user nickname.
     *
     * @param string $nickname
     * @return self
     */
    public function setNickname(string $nickname) : self
    {
        $this->nickname = $nickname;

        return $this;
    }

    /**
     * Creates a new User.
     *
     * @since 3.4.1
     *
     * @param array $data
     * @return self
     * @throws SentryException
     */
    public static function create(array $data = []) : User
    {
        return (static::seed($data))->save();
    }

    /**
     * Deletes the given user instance.
     *
     * @since 3.4.1
     *
     * @return bool
     * @throws SentryException
     */
    public function delete() : bool
    {
        if (! $this->getId()) {
            // @TODO: Update to specific exception after deciding the folder location of where that should live {JO: 2021-03-26}
            throw new SentryException('Deleting a user requires a valid user ID');
        }

        if (! wp_delete_user($this->getId())) {
            // @TODO: Update to specific exception after deciding the folder location of where that should live {JO: 2021-03-26}
            throw new SentryException('User could not be deleted');
        }

        return true;
    }

    /**
     * Saves the current user instance.
     *
     * @since 3.4.1
     *
     * @return self
     * @throws SentryException
     */
    public function save() : User
    {
        // @TODO: we should support additional fields in convertToSource in the future {FN: 2021-03-30}
        $id = wp_insert_user((new UserAdapter($this))->convertToSource());

        if (is_a($id, 'WP_Error', true)) {
            // @TODO: Update to specific exception after deciding the folder location of where that should live {JO: 2021-03-26}
            throw new SentryException('Failed to save the User model.');
        }

        $this->setId($id);

        return $this;
    }

    /**
     * Seeds an instance of a User without saving,.
     *
     * @since 3.4.1
     *
     * @param array $data
     * @return User
     */
    public static function seed(array $data = []) : User
    {
        return (new User())->setProperties($data);
    }

    /**
     * Updates the given user instance.
     *
     * @since 3.4.1
     *
     * @return self
     * @throws SentryException
     */
    public function update() : User
    {
        return $this->save();
    }

    /**
     * Gets a User.
     *
     * @since 3.4.1
     *
     * @param int|string|null $identifier an ID, email, or handle
     * @return User|null user object, if found
     */
    public static function get($identifier)
    {
        /* @NOTE we expect to pass an integer to identify a user ID, a numerical string should be assumed to be a login handle only */
        if (is_int($identifier)) {
            return static::getById($identifier);
        }

        /* @NOTE this accounts for an email string used alternatively as a login handle */
        if (false !== filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return static::getByEmail($identifier) ?? static::getByHandle($identifier);
        }

        if (is_string($identifier)) {
            return static::getByHandle($identifier);
        }

        return null;
    }

    /**
     * Gets a User associated with a given email.
     *
     * @since 3.4.1
     *
     * @param string $email the email to search for
     * @return User|null
     */
    public static function getByEmail(string $email)
    {
        if ($user = get_user_by('email', $email)) {
            return static::seed((new UserAdapter($user))->convertFromSource());
        }

        return null;
    }

    /**
     * Gets a User associated with a given login handle.
     *
     * @since 3.4.1
     *
     * @param string $handle the login to search for
     * @return User|null
     */
    public static function getByHandle(string $handle)
    {
        if ($user = get_user_by('login', $handle)) {
            return static::seed((new UserAdapter($user))->convertFromSource());
        }

        return null;
    }

    /**
     * Gets a User associated with a given ID.
     *
     * @since 3.4.1
     *
     * @param int $id the id to search for
     * @return User|null
     */
    public static function getById(int $id)
    {
        if ($user = get_user_by('id', $id)) {
            return static::seed((new UserAdapter($user))->convertFromSource());
        }

        return null;
    }

    /**
     * Gets the currently logged in user.
     *
     * @since 3.4.1
     *
     * @return User|null
     */
    public static function getCurrent()
    {
        $user = (new UserAdapter(wp_get_current_user()))->convertFromSource();

        if (ArrayHelper::get($user, 'id', 0) > 0) {
            return static::seed($user);
        }

        return null;
    }

    /**
     * Determines whether the user is logged in.
     *
     * @since 3.4.1
     *
     * @return bool
     */
    public function isLoggedIn() : bool
    {
        if (! $currentUser = static::getCurrent()) {
            return false;
        }

        return $currentUser->id === $this->id;
    }

    /**
     * Determines if the user instance is registered in database.
     *
     * @since 3.4.1
     *
     * @return bool
     */
    public function isRegistered() : bool
    {
        if ($this->id > 0) {
            return username_exists($this->getHandle());
        }

        return false;
    }
}
