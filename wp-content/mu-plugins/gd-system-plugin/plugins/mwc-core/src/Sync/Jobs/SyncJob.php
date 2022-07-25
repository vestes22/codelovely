<?php

namespace GoDaddy\WordPress\MWC\Core\Sync\Jobs;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Traits\CanBulkAssignPropertiesTrait;
use stdClass;

/**
 * Base model of a sync job.
 *
 * @since 2.13.0
 */
class SyncJob extends AbstractModel
{
    use CanBulkAssignPropertiesTrait;

    /** @var int unique identifier of the job */
    protected $id;

    /** @var int timestamp when the job was created */
    protected $createdAt;

    /** @var int timestamp when the job was last updated */
    protected $updatedAt;

    /** @var int the number of objects that should be handled in a single request */
    protected $batchSize;

    /** @var string identifier of the job's initiator, such as a feature, component or plugin */
    protected $owner;

    /** @var string the current status of the job */
    protected $status;

    /** @var string type of object, such as order or customer */
    protected $objectType;

    /** @var int[] list of object identifiers that the job is expected to handle */
    protected $objectIds = [];

    /** @var int[] list of identifiers of objects that were created by the job */
    protected $createdIds = [];

    /** @var int[] list of identifiers of objects that were updated by the job */
    protected $updatedIds = [];

    /** @var stdClass[] any errors occurred in job processing, with and properties */
    protected $errors = [];

    /** @var string used to store sync jobs */
    protected static $prefix = 'mwc_sync_job_';

    /**
     * Creates a new sync job and saves it.
     *
     * @since 2.13.0
     *
     * @param array $data associative array of job properties
     * @return self
     * @throws Exception
     */
    public static function create(array $data = []) : SyncJob
    {
        return (static::seed($data))
            ->setCreatedAt((new DateTime('now'))->getTimestamp())
            ->save();
    }

    /**
     * Gets a sync job from storage.
     *
     * @since 2.13.0
     *
     * @param int $id sync job ID
     * @return self|null
     */
    public static function get($id)
    {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare("
            SELECT option_value as value
            FROM $wpdb->options
            WHERE option_name LIKE %s
            AND option_id=%d
            LIMIT 1
        ", static::$prefix.'%', (int) $id), ARRAY_A);

        if (! $value = ArrayHelper::get($result, 'value')) {
            return null;
        }

        if (! $data = json_decode($value, true)) {
            return null;
        }

        return static::seed($data)->setId((int) $id);
    }

    /**
     * Constructor.
     */
    final public function __construct()
    {
        // final constructor used to ensure that all subclasses can be instantiated without parameters
    }

    /**
     * Updates a sync job.
     *
     * @since 2.13.0
     *
     * @param array $data properties to update
     * @return self
     * @throws Exception
     */
    public function update(array $data = []) : SyncJob
    {
        global $wpdb;

        if (! $this->id) {
            throw new Exception('Unable to update sync job: missing job ID.');
        }

        if (! static::get($this->id)) {
            throw new Exception('Unable to update sync job: job could not be found.');
        }

        $this->setProperties($data);
        $this->setUpdatedAt((new DateTime('now'))->getTimestamp());

        $wpdb->update(
            $wpdb->options,
            ['option_value' => json_encode($this->toArray())],
            ['option_id' => $this->id]
        );

        return $this;
    }

    /**
     * Deletes a sync job.
     *
     * @since 2.13.0
     *
     * @return bool
     */
    public function delete() : bool
    {
        global $wpdb;

        if (! $this->id) {
            return false;
        }

        return (bool) $wpdb->delete(
            $wpdb->options,
            ['option_id' => $this->id],
            ['%d']
        );
    }

    /**
     * Saves the sync job in its current state.
     *
     * @since 2.13.0
     *
     * @return self
     * @throws Exception
     */
    public function save() : SyncJob
    {
        global $wpdb;

        if (! empty($this->id)) {
            return $this->update();
        }

        $wpdb->insert(
            $wpdb->options,
            ['option_name' => uniqid(static::$prefix, false), 'option_value' => json_encode($this->toArray())],
            ['%s', '%s']
        );

        return $this->setId($wpdb->insert_id);
    }

    /**
     * Seeds a sync job instance without saving it.
     *
     * @since 2.13.0
     *
     * @param array $data associative array of job properties
     * @return self
     */
    public static function seed(array $data = []) : SyncJob
    {
        return (new static())->setProperties(ArrayHelper::where($data, static function ($value) {
            return null !== $value;
        }));
    }

    /**
     * Sets the sync job ID.
     *
     * @since 2.13.0
     *
     * @param int $value
     * @return self
     */
    public function setId(int $value) : SyncJob
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the sync job ID.
     *
     * @since 2.13.0
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Sets the sync job created at timestamp.
     *
     * @since 2.13.0
     *
     * @param int $value
     * @return self
     */
    public function setCreatedAt(int $value) : SyncJob
    {
        $this->createdAt = $value;

        return $this;
    }

    /**
     * Gets the sync job created at timestamp.
     *
     * @since 2.13.0
     *
     * @return int
     */
    public function getCreatedAt() : int
    {
        return $this->createdAt;
    }

    /**
     * Sets the sync job updated at timestamp.
     *
     * @since 2.13.0
     *
     * @param int $value
     * @return self
     */
    public function setUpdatedAt(int $value) : SyncJob
    {
        $this->updatedAt = $value;

        return $this;
    }

    /**
     * Gets the sync job updated at timestamp.
     *
     * @since 2.13.0
     *
     * @return int
     */
    public function getUpdatedAt() : int
    {
        return $this->updatedAt;
    }

    /**
     * Sets the sync job batch size.
     *
     * @since 2.13.0
     *
     * @param int $value
     * @return self
     */
    public function setBatchSize(int $value) : SyncJob
    {
        $this->batchSize = $value;

        return $this;
    }

    /**
     * Gets the sync job batch size.
     *
     * @since 2.13.0
     *
     * @return int
     */
    public function getBatchSize() : int
    {
        return $this->batchSize;
    }

    /**
     * Sets the sync job owner.
     *
     * @since 2.13.0
     *
     * @param string $value
     * @return self
     */
    public function setOwner(string $value) : SyncJob
    {
        $this->owner = $value;

        return $this;
    }

    /**
     * Gets the sync job owner.
     *
     * @since 2.13.0
     *
     * @return string
     */
    public function getOwner() : string
    {
        return $this->owner;
    }

    /**
     * Sets the sync job status.
     *
     * @since 2.13.0
     *
     * @param string $value
     * @return self
     */
    public function setStatus(string $value) : SyncJob
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Gets the sync job status.
     *
     * @since 2.13.0
     *
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * Sets the sync job owner.
     *
     * @since 2.13.0
     *
     * @param string $value
     * @return self
     */
    public function setObjectType(string $value) : SyncJob
    {
        $this->objectType = $value;

        return $this;
    }

    /**
     * Gets the sync job owner.
     *
     * @since 2.13.0
     *
     * @return string
     */
    public function getObjectType() : string
    {
        return $this->objectType;
    }

    /**
     * Sets the sync job object IDs.
     *
     * @since 2.13.0
     *
     * @param int[] $value
     * @return self
     */
    public function setObjectIds(array $value) : SyncJob
    {
        $this->objectIds = $value;

        return $this;
    }

    /**
     * Gets the sync job object IDs.
     *
     * @since 2.13.0
     *
     * @return int[]
     */
    public function getObjectIds() : array
    {
        return $this->objectIds;
    }

    /**
     * Sets the sync job created object IDs.
     *
     * @since 2.13.0
     *
     * @param int[] $value
     * @return self
     */
    public function setCreatedIds(array $value) : SyncJob
    {
        $this->createdIds = $value;

        return $this;
    }

    /**
     * Gets the sync job created object IDs.
     *
     * @since 2.13.0
     *
     * @return int[]
     */
    public function getCreatedIds() : array
    {
        return $this->createdIds;
    }

    /**
     * Sets the sync job updated object IDs.
     *
     * @since 2.13.0
     *
     * @param int[] $value
     * @return self
     */
    public function setUpdatedIds(array $value) : SyncJob
    {
        $this->updatedIds = $value;

        return $this;
    }

    /**
     * Gets the sync job updated object IDs.
     *
     * @since 2.13.0
     *
     * @return int[]
     */
    public function getUpdatedIds() : array
    {
        return $this->updatedIds;
    }

    /**
     * Sets the sync job errors.
     *
     * @since 2.13.0
     *
     * @param stdClass[] $value
     * @return self
     */
    public function setErrors(array $value) : SyncJob
    {
        $this->errors = $value;

        return $this;
    }

    /**
     * Gets the sync job errors.
     *
     * @since 2.13.0
     *
     * @return stdClass[]
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
}
