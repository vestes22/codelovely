<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Events\OrderCreatedEvent;
use GoDaddy\WordPress\MWC\Core\Exceptions\Payments\PoyntOrderPushSyncJobException;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Sync\Jobs\PushSyncJob;

class PoyntOrderPushSubscriber implements SubscriberContract
{
    /** @var bool keep track of whether the create order event has already been processed */
    protected static $hasProcessedEvent = false;

    /**
     * @param EventContract $event
     * @return void
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldSendEvent($event)) {
            return;
        }

        try {
            $this->sendEventToSyncOrderJob($event);
        } catch (Exception $exception) {
            throw new PoyntOrderPushSyncJobException($exception->getMessage());
        }
    }

    /**
     * Send the Event to the sync order job.
     *
     * @param EventContract $event
     * @return void
     * @throws Exception
     */
    protected function sendEventToSyncOrderJob(EventContract $event)
    {
        $orderId = ArrayHelper::get($event->getData(), 'id');

        if ($orderId && is_numeric($orderId)) {
            PushSyncJob::create([
                'owner'      => 'poynt_order',
                'batchSize'  => 1,
                'objectType' => 'order',
                'objectIds'  => ArrayHelper::wrap($orderId),
            ]);
        }
    }

    /**
     * Determines whether the given event should be sent.
     *
     * @param EventContract $event event object
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldSendEvent(EventContract $event): bool
    {
        // bail if we've already processed the event (CreateOrderEvent gets fired twice, without context)
        if (static::$hasProcessedEvent) {
            return false;
        }

        $wcOrder = OrdersRepository::get(ArrayHelper::get($event->getData(), 'id'));
        if (! $wcOrder || ! Poynt::shouldPushOrderDetailsToPoynt((new OrderAdapter($wcOrder))->convertFromSource())) {
            return false;
        }

        if (! $event instanceof OrderCreatedEvent) {
            return false;
        }

        static::$hasProcessedEvent = true;

        return true;
    }
}
