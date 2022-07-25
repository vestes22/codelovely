<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;

abstract class AbstractNoticeSubscriber implements SubscriberContract
{
    /**
     * Gets the notice message from the given event.
     *
     * @param EventContract $event
     *
     * @return string
     */
    abstract protected function getMessage(EventContract $event) : string;

    /**
     * Gets the notice type from the given event.
     *
     * @param EventContract $event
     *
     * @return string
     */
    abstract protected function getType(EventContract $event) : string;

    /**
     * Handles the event.
     *
     * @param EventContract $event
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        wc_add_notice($this->getMessage($event), $this->getType($event));
    }

    /**
     * Determines if the event should be handled.
     *
     * As a baseline, we need to check if the notice function exists. These events can be triggered in the admin or via
     * AJAX, so notices might not apply.
     *
     * TODO: add a wrapper for this customer-facing notice functionality into something like WooCommerceRepository {@cwiseman 2021-06-03}
     *
     * @param EventContract $event
     *
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return function_exists('wc_add_notice') && ! is_admin();
    }
}
