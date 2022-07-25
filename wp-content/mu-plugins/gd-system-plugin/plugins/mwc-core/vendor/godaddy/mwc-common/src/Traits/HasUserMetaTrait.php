<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait used for storing data as WP user meta.
 *
 * @since 1.0.0
 */
trait HasUserMetaTrait
{
    /** @var int user ID used to load/store the metadata */
    protected $userId;

    /** @var string meta key used to load/store the metadata */
    protected $metaKey;

    /** @var string|array|int|float value to be stored as metadata */
    protected $value;

    /**
     * Loads and returns the value stored in the user metadata.
     *
     * It sets the value property to the value loaded from the user metadata or the default value.
     *
     * @since 1.0.0
     *
     * @param string|array|int|float $defaultValue value used if the user metadata doesn't exist
     * @return string|array|int|float
     */
    public function loadUserMeta($defaultValue)
    {
        if (! metadata_exists('user', $this->userId, $this->metaKey)) {
            return $defaultValue;
        }

        $this->value = get_user_meta($this->userId, $this->metaKey, true);

        return $this->value;
    }

    /**
     * Gets the value property.
     *
     * @since 1.0.0
     *
     * @return string|array|int|float
     */
    public function getUserMeta()
    {
        return $this->value;
    }

    /**
     * Sets the value property.
     *
     * @since 1.0.0
     *
     * @param string|array|int|float $value value to store
     * @return self
     */
    public function setUserMeta($value) : self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Stores the value property as user metadata.
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function saveUserMeta() : self
    {
        update_user_meta($this->userId, $this->metaKey, $this->value);

        return $this;
    }
}
