<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayEnabledEvent;

class CompleteSetUpPaymentsTaskSubscriber
{
    /** @var string the option key for the wc completed task list */
    protected $wc_completed_task_option_key = 'woocommerce_task_list_tracked_completed_tasks';

    /**
     * Marks the Set up payments task as completed when GoDaddy Payments is enabled.
     *
     * @since 2.13.0
     *
     * @param EventContract $event
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        $this->maybeMarkTaskAsCompleted($event);
    }

    /**
     * Determines whether the subscriber should handle the given event.
     *
     * @since 2.13.0
     *
     * @param EventContract $event
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof PaymentGatewayEnabledEvent;
    }

    /**
     * Adds payments to the list of completed tasks.
     *
     * @since 2.13.0
     *
     * @param EventContract $event
     */
    protected function maybeMarkTaskAsCompleted(EventContract $event)
    {
        if ('poynt' !== ArrayHelper::get($event->getData(), 'paymentGateway.id')) {
            return;
        }

        $option = get_option($this->wc_completed_task_option_key, []);

        if (ArrayHelper::contains($option, 'payments')) {
            return;
        }

        $option[] = 'payments';
        update_option($this->wc_completed_task_option_key, $option);
    }
}
