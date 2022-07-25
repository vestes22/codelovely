<?php

namespace GoDaddy\WordPress\MWC\Core\Sync\Events\Producers;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Sync\Events\AbstractPullEvent;
use GoDaddy\WordPress\MWC\Core\Sync\Jobs\SyncJob;

/**
 * The producer for pull sync events.
 */
class PullEventsProducer implements ProducerContract
{
    /** @var string the pull objects action hook */
    const ACTION_PULL_OBJECTS = 'mwc_pull_objects';

    /**
     * Sets up the Coupon events producer.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public function setup()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.18.1', __CLASS__.'::load');

        $this->load();
    }

    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        // set up the recurring action schedules for pull events
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'setupSchedules'])
            ->execute();

        // broadcast an event when called by Action Scheduler
        Register::action()
            ->setGroup(self::ACTION_PULL_OBJECTS)
            ->setHandler([$this, 'broadcastEvent'])
            ->execute();
    }

    /**
     * Broadcasts an event when called by Action Scheduler.
     *
     * This is a callback function for {@see as_schedule_recurring_action()}.
     *
     * @internal
     *
     * @param string|mixed $eventClass
     * @throws Exception
     */
    public function broadcastEvent($eventClass)
    {
        /* class must exist and implement {@see EventContract} */
        if (! is_string($eventClass) || ! class_exists($eventClass) || ! in_array(EventContract::class, (array) class_implements($eventClass), true)) {
            return;
        }

        Events::broadcast(new $eventClass(SyncJob::create()));
    }

    /**
     * Sets up the recurring action schedules for pull events.
     *
     * @internal
     *
     * @throws Exception
     */
    public function setupSchedules()
    {
        foreach (Configuration::get('sync.pulls') as $pull) {
            $interval = ArrayHelper::get($pull, 'interval');
            $event = ArrayHelper::get($pull, 'eventClass');

            if (! is_int($interval) || ! is_string($event)) {
                continue;
            }

            /** @var string|AbstractPullEvent $event class name */
            if (! $event::shouldSchedule() || $this->hasScheduledPull($event)) {
                continue;
            }

            $this->schedulePull($interval, $event);
        }
    }

    /**
     * Determines if there is a scheduled action for a given event.
     *
     * @param string $event class name
     * @return bool
     */
    private function hasScheduledPull(string $event) : bool
    {
        return (bool) as_next_scheduled_action(self::ACTION_PULL_OBJECTS, [$event]);
    }

    /**
     * Schedules a pull action.
     *
     * @param int $interval schedule interval
     * @param string $event class name
     */
    private function schedulePull(int $interval, string $event)
    {
        as_schedule_recurring_action(
            (new DateTime('now'))->getTimestamp(),
            $interval,
            self::ACTION_PULL_OBJECTS,
            [$event]
        );
    }
}
