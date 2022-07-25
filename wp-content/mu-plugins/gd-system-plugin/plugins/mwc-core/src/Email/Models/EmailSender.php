<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Models;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Core\Email\Cache\Types\CacheEmailSender;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceAuthProviderException;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceException;
use GoDaddy\WordPress\MWC\Core\Email\Repositories\EmailSenderRepository;

class EmailSender extends AbstractModel
{
    use CanBulkAssignPropertiesTrait, CanConvertToArrayTrait {
        CanConvertToArrayTrait::toArray as traitToArray;
    }

    /** @var string */
    const STATUS_PENDING = 'PENDING';

    /** @var string */
    const STATUS_VERIFIED = 'VERIFIED';

    /** @var string */
    const STATUS_UNVERIFIED = 'UNVERIFIED';

    /** @var int unique ID */
    protected $id;

    /** @var string */
    protected $emailAddress;

    /** @var DateTime */
    protected $verifiedAt;

    /** @var string */
    protected $verifiedBy;

    /** @var string */
    protected $status;

    /** @var CacheEmailSender */
    protected $cache;

    /**
     * Constructor.
     */
    final public function __construct()
    {
        // final constructor used to ensure that all subclasses can be instantiated without parameters
    }

    /**
     * Gets the unique ID.
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Sets the unique ID.
     *
     * @param int $id
     * @return EmailSender
     */
    public function setId(int $id) : EmailSender
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the email address.
     *
     * @return string
     */
    public function getEmailAddress() : string
    {
        return $this->emailAddress;
    }

    /**
     * Sets the email address.
     *
     * @param string $emailAddress
     * @return EmailSender
     */
    public function setEmailAddress(string $emailAddress) : EmailSender
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Gets the date & time object when the sender is verified.
     *
     * @return DateTime|null
     */
    public function getVerifiedAt()
    {
        return $this->verifiedAt;
    }

    /**
     * Sets the date & time object when the sender is verified.
     *
     * @param DateTime $verifiedAt
     *
     * @return EmailSender
     */
    public function setVerifiedAt(DateTime $verifiedAt) : EmailSender
    {
        $this->verifiedAt = $verifiedAt;

        return $this;
    }

    /**
     * Gets the method used to verify the sender.
     *
     * @return string|null
     */
    public function getVerifiedBy()
    {
        return $this->verifiedBy;
    }

    /**
     * Sets the method used to verify the sender.
     *
     * @param string $verifiedBy
     * @return EmailSender
     */
    public function setVerifiedBy(string $verifiedBy) : EmailSender
    {
        $this->verifiedBy = $verifiedBy;

        return $this;
    }

    /**
     * Gets the status.
     *
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * Sets the status.
     *
     * @param string $status
     * @return EmailSender
     */
    public function setStatus(string $status) : EmailSender
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Determines if sender is verified or not.
     *
     * @return bool
     */
    public function isVerified() : bool
    {
        return static::STATUS_VERIFIED === strtoupper($this->getStatus());
    }

    /**
     * Creates a new instance of the sender and saves it.
     *
     * @param string $emailAddress
     * @return EmailSender
     * @throws EmailsServiceAuthProviderException
     * @throws EmailsServiceException
     */
    public static function create(string $emailAddress = '') : EmailSender
    {
        $data = [];

        if (! empty($emailAddress)) {
            $data = EmailSenderRepository::create($emailAddress);
        }

        return static::seed($data)->save();
    }

    /**
     * Gets an instance of the sender model class, if found.
     *
     * @param string $emailAddress
     * @return EmailSender|null
     */
    public static function get($emailAddress)
    {
        try {
            return static::getOrFail($emailAddress);
        } catch (EmailsServiceException $e) {
            return null;
        }
    }

    /**
     * Gets a sender data from cache, otherwise, tries to fetch the data from Emails Service API.
     *
     * @param string $emailAddress
     * @return EmailSender
     * @throws EmailsServiceException
     */
    public static function getOrFail(string $emailAddress) : EmailSender
    {
        $fromApi = false;
        if (! $data = CacheEmailSender::for($emailAddress)->get()) {
            $data = EmailSenderRepository::getOrCreate($emailAddress);
            $fromApi = true;
        }

        $sender = static::seed($data);
        if ($fromApi) {
            $sender->save();
        }

        return $sender;
    }

    /**
     * Updates the sender data in cache.
     *
     * @return EmailSender
     */
    public function update() : EmailSender
    {
        return $this->save();
    }

    /**
     * Deletes sender data from cache.
     *
     * @return EmailSender
     */
    public function delete() : EmailSender
    {
        $this->getCacheInstance()->clear();

        return $this;
    }

    /**
     * Converts all model data properties to an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        $data = $this->traitToArray();

        if ($verifiedAt = $this->getVerifiedAt()) {
            $data['verifiedAt'] = $verifiedAt->format('Y-m-d H:i:s');
        }

        return ArrayHelper::except($data, ['cache']);
    }

    /**
     * Saves the sender data into cache.
     *
     * @return EmailSender
     */
    public function save() : EmailSender
    {
        $data = $this->toArray();

        $this->getCacheInstance()->set($data);

        return $this;
    }

    /**
     * Gets the corresponding cache instance.
     *
     * @return CacheEmailSender
     */
    protected function getCacheInstance() : CacheEmailSender
    {
        if (null === $this->cache) {
            $this->cache = CacheEmailSender::for($this->getEmailAddress());
        }

        return $this->cache;
    }

    /**
     * Seeds an instance of a EmailSender without saving,.
     *
     * @param array $data
     * @return EmailSender
     */
    public static function seed(array $data = []) : EmailSender
    {
        $verifiedAt = ArrayHelper::get($data, 'verifiedAt');
        if ($verifiedAt && is_string($verifiedAt)) {
            ArrayHelper::set($data, 'verifiedAt', DateTime::createFromFormat('Y-m-d H:i:s', $verifiedAt));
        }

        return (new static())->setProperties(array_filter($data));
    }
}
