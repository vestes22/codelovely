<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Contracts;

/**
 * Subscriber contract.
 */
interface SubscriberContract
{
    public function handle(EventContract $event);
}
