<?php

namespace GoDaddy\WordPress\MWC\Core\Sync\Jobs;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * A sync job for pushing objects on a schedule.
 *
 * @since 2.14.0
 */
class PushSyncJob extends SyncJob
{
    /** @var string placeholder used in scheduled action hook identifiers */
    protected $hookPlaceholder = 'mwc_push_%s_objects';

    /**
     * Creates and schedules a push job.
     *
     * @since 2.14.0
     *
     * @param array $data
     * @return self
     * @throws Exception
     */
    public static function create(array $data = []) : SyncJob
    {
        if (! ArrayHelper::get($data, 'owner')) {
            throw new Exception('The push job must have an owner.');
        }

        if (! ArrayHelper::get($data, 'batchSize')) {
            throw new Exception('The push job must have a batch size.');
        }

        return parent::create($data)->schedule();
    }

    /**
     * Schedules the push job.
     *
     * This may schedule multiple Action Scheduler actions depending on the batch size configured in the job.
     *
     * @since 2.14.0
     *
     * @return self
     */
    private function schedule() : PushSyncJob
    {
        $batches = array_chunk($this->getObjectIds(), $this->getBatchSize());

        foreach ($batches as $batchIds) {
            as_schedule_single_action(
                (new DateTime('now'))->getTimestamp(),
                sprintf($this->hookPlaceholder, $this->getOwner()),
                [
                    'jobId'     => $this->getId(),
                    'objectIds' => $batchIds,
                ]
            );
        }

        return $this;
    }
}
