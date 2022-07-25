<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Message;

use DateInterval;
use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;

/**
 * MessagesController class.
 *
 * @since 1.0.0
 */
class Message extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /**
     * Message type: recommendation.
     *
     * @var string
     */
    const TYPE_RECOMMENDATION = 'RECOMMENDATION';

    /**
     * Message ID.
     *
     * @var string
     */
    protected $id;

    /**
     * Message type.
     *
     * @var string
     */
    protected $type;

    /**
     * Message subject.
     *
     * @var string
     */
    protected $subject;

    /**
     * Message body.
     *
     * @var string
     */
    protected $body;

    /**
     * Message published date.
     *
     * @var DateTime
     */
    protected $publishedAt;

    /**
     * Message expiration date.
     *
     * @var DateTime
     */
    protected $expiredAt;

    /**
     * Message "Do Not Expire" status.
     *
     * @var bool
     */
    protected $doNotExpire = false;

    /**
     * Message actions.
     *
     * @var array
     */
    protected $actions = [];

    /**
     * Message rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Message links.
     *
     * @var array
     */
    protected $links = [];

    /**
     * Message contexts.
     *
     * @var array
     */
    protected $contexts = [];

    /**
     * Message status context.
     *
     * @var string
     */
    protected $contextStatus;

    /**
     * Message constructor.
     *
     * @param array $messageData
     */
    public function __construct(array $messageData)
    {
        $this->setProperties($messageData);
    }

    /**
     * Checks if message is expired or not.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function isExpired() : bool
    {
        if ($this->shouldNotExpire()) {
            return false;
        }

        // get defined expiration date
        $expiredAt = $this->getExpiredAt();

        // calculate expiration date based on the publishing date
        if (! $expiredAt && $publishedAt = $this->getPublishedAt()) {
            try {
                $expiredAt = (clone $publishedAt)->add(new DateInterval('P30D'));
            } catch (Exception $e) {
                return false;
            }
        }

        // bail if expired at datetime is not set, so we assume it's not expired
        if (! $expiredAt) {
            return false;
        }

        // evaluate expiration date
        return new DateTime() > $expiredAt;
    }

    /**
     * Sets message ID.
     *
     * @param string $id
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function setId(string $id) : self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets message type.
     *
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type) : self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Sets message subject.
     *
     * @param string $subject
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function setSubject(string $subject) : self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets message body.
     *
     * @param string $body
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function setBody(string $body) : self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Sets message published at.
     *
     * @param DateTime $publishedAt
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function setPublishedAt(DateTime $publishedAt) : self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * Sets message expired at.
     *
     * @param DateTime|null $expiredAt
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function setExpiredAt($expiredAt) : self
    {
        if ($expiredAt) {
            $this->expiredAt = $expiredAt;
        }

        return $this;
    }

    /**
     * Sets whether message would never expire or not.
     *
     * @param bool $doNotExpire
     *
     * @return self
     */
    public function setDoNotExpire(bool $doNotExpire = false) : self
    {
        $this->doNotExpire = $doNotExpire;

        return $this;
    }

    /**
     * Sets message actions.
     *
     * @param array $actions
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function setActions(array $actions) : self
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * Sets message rules.
     *
     * @param array $rules
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function setRules(array $rules) : self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Sets message links.
     *
     * @param array $links
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function setLinks(array $links) : self
    {
        $this->links = $links;

        return $this;
    }

    /**
     * Sets the message contexts.
     *
     * @param array $contexts the message contexts
     *
     * @since 1.3.0
     *
     * @return self
     */
    public function setContexts(array $contexts) : self
    {
        $this->contexts = $contexts;

        return $this;
    }

    /**
     * Sets the message context status.
     *
     * @param string $contextStatus the message context status
     *
     * @since 1.3.0
     *
     * @return self
     */
    public function setContextStatus(string $contextStatus) : self
    {
        $this->contextStatus = $contextStatus;

        return $this;
    }

    /**
     * Gets message ID.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets message type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets message subject.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Gets message body.
     *
     * @since 1.0.0
     *
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Gets message published datetime object.
     *
     * @since 1.0.0
     *
     * @return DateTime|null
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * Gets message expired datetime object.
     *
     * @since 1.0.0
     *
     * @return null|DateTime
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * Gets "Do Not Expire" status.
     *
     * @return bool
     */
    public function getDoNotExpire() : bool
    {
        return $this->doNotExpire;
    }

    /**
     * Gets message actions.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Gets message rules.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Gets message links.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Gets the message contexts.
     *
     * @since 1.3.0
     *
     * @return array
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * Gets the message context status.
     *
     * @since 1.3.0
     *
     * @return string
     */
    public function getContextStatus() : string
    {
        return $this->contextStatus;
    }

    /**
     * Determines whether the message should not expire.
     *
     * @see Message::getDoNotExpire() alias
     *
     * @return bool
     */
    public function shouldNotExpire() : bool
    {
        return $this->getDoNotExpire();
    }

    /**
     * Gets the associated message status.
     *
     * @since 1.0.0
     *
     * @param null|int|string $userId
     *
     * @return MessageStatus
     */
    public function status($userId = null) : MessageStatus
    {
        if (! is_numeric($userId) || ! $userId) {
            $userId = User::getCurrent()->getId();
        }

        return new MessageStatus($this, (int) $userId);
    }

    /**
     * Converts all class data properties to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        $data['status'] = $this->status()->toArray();

        return $data;
    }

    /**
     * Updates a given instance of the model class.
     *
     * @return self
     * @throws Exception
     */
    public function update() : Message
    {
        parent::update();

        Events::broadcast($this->buildEvent('message', 'update'));

        return $this;
    }
}
