<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use WC_Logger;

abstract class AbstractLogSubscriber implements SubscriberContract
{
    /** @var string log ID */
    protected $id;

    /**
     * Gets the log message from the given event.
     *
     * @param EventContract $event
     *
     * @return string
     */
    abstract protected function getMessage(EventContract $event) : string;

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

        $this->getLogger()->add($this->id, $this->getMessage($event));
    }

    /**
     * Gets an instance of WooCommerce logger class.
     *
     * @return WC_Logger
     */
    protected function getLogger() : WC_Logger
    {
        return new WC_Logger();
    }

    /**
     * Determines if the event should be handled.
     *
     * @param EventContract $event
     *
     * @return bool
     */
    abstract protected function shouldHandle(EventContract $event) : bool;
}
