<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\Sync\Jobs\PushSyncJob;

/**
 * A subscriber to order and transaction events for registering Poynt webhooks.
 */
class RegisterWebhooksSubscriber implements SubscriberContract
{
    /** @var array */
    const WEBHOOK_TOPICS = [
        'ORDER_CANCELLED',
        'ORDER_COMPLETED',
        'ORDER_UPDATED',
        'TRANSACTION_AUTHORIZED',
        'TRANSACTION_CAPTURED',
        'TRANSACTION_REFUNDED',
        'TRANSACTION_UPDATED',
        'TRANSACTION_VOIDED',
    ];

    /**
     * Determines whether the event should be handled.
     *
     * @param EventContract $event
     *
     * @return bool
     * @throws Exception
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return Onboarding::STATUS_CONNECTED === Onboarding::getStatus();
    }

    /**
     * Handles the event.
     *
     * @param EventContract $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        $this->maybeRegisterWebhooksJob();
    }

    /**
     * Schedule job to register Poynt webhooks, if not already registered.
     *
     * @throws Exception
     */
    protected function maybeRegisterWebhooksJob()
    {
        if (get_option('mwc_payments_poynt_onboarding_webhooksRegistered')) {
            return;
        }

        PushSyncJob::create([
            'owner'      => 'register_poynt_webhooks',
            'batchSize'  => count(static::WEBHOOK_TOPICS),
            'objectType' => 'webhooks',
            'objectIds'  => static::WEBHOOK_TOPICS,
        ]);
    }
}
