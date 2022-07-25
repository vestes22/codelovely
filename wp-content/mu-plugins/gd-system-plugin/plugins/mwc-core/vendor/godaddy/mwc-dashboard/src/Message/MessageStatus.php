<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Message;

use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasUserMetaTrait;

class MessageStatus
{
    use HasUserMetaTrait;
    use CanConvertToArrayTrait;

    /**
     * Message status: Unread.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const STATUS_UNREAD = 'unread';

    /**
     * Message status: Read.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const STATUS_READ = 'read';

    /**
     * Message status: Deleted.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const STATUS_DELETED = 'deleted';

    /**
     * Related message ID.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $messageId;

    /**
     * MessageStatus constructor.
     *
     * @param Message $message
     * @param int     $userId
     */
    public function __construct(Message $message, int $userId)
    {
        $this->messageId = $message->getId();
        $this->userId = $userId;
        $this->metaKey = '_mwc_dashboard_message_status_'.$this->messageId;

        $this->loadUserMeta(static::STATUS_UNREAD);
    }

    /**
     * Checks if message status is deleted or not.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function isDeleted() : bool
    {
        return self::STATUS_DELETED === $this->getStatus();
    }

    /**
     * Gets the message status state.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getStatus() : string
    {
        return $this->getUserMeta() ?? static::STATUS_UNREAD;
    }
}
