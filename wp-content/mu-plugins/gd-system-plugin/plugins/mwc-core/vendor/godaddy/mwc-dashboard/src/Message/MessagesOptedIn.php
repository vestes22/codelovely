<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Message;

use GoDaddy\WordPress\MWC\Common\Settings\Models\AbstractSetting;
use GoDaddy\WordPress\MWC\Common\Traits\HasUserMetaTrait;

/**
 * Holds the user preference regarding MWC Dashboard messages (opted in or opted out).
 */
class MessagesOptedIn extends AbstractSetting
{
    use HasUserMetaTrait;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     *
     * @param int|null $userId
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->metaKey = '_mwc_dashboard_messages_opted_in';

        // defaults to false because merchants are opted out by default
        $this->loadUserMeta(false);
    }

    /**
     * Opts in the user for the Dashboard messages.
     *
     * @since 1.0.0
     */
    public function optIn()
    {
        $this->setUserMeta(true);
        $this->saveUserMeta();
    }

    /**
     * Opts out the user for the Dashboard messages.
     *
     * @since 1.0.0
     */
    public function optOut()
    {
        $this->setUserMeta(false);
        $this->saveUserMeta();
    }

    /**
     * Gets the ID of the user associated with this preference.
     *
     * @since 1.0.0
     *
     * @return int
     */
    public function getUserId() : int
    {
        return $this->userId;
    }
}
