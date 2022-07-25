<?php

namespace GoDaddy\WordPress\MWC\Core\Sync\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Core\Sync\Jobs\SyncJob;

/**
 * Abstract pull event.
 *
 * This abstract event may be extended by implementations and broadcast whenever a recurring pull schedule is fired.
 *
 * @since 2.13.0
 */
abstract class AbstractPullEvent implements EventContract
{
    /** @var SyncJob the job object */
    protected $job;

    /**
     * Abstract pull event constructor.
     *
     * @since 2.13.0
     */
    public function __construct(SyncJob $job)
    {
        $this->job = $job;
    }

    /**
     * Determines if the event should be scheduled.
     *
     * @since 2.13.0
     *
     * @return bool
     */
    public static function shouldSchedule() : bool
    {
        return true;
    }
}
