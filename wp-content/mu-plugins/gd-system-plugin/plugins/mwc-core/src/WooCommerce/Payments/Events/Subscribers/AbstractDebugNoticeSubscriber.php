<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

abstract class AbstractDebugNoticeSubscriber extends AbstractNoticeSubscriber
{
    /**
     * Gets the notice type.
     *
     * For debug logging, this is always just "notice."
     *
     * @param EventContract $event
     *
     * @return string
     */
    protected function getType(EventContract $event) : string
    {
        return 'notice';
    }

    /**
     * Determines if the event should be handled.
     *
     * TODO: add a wrapper for this customer-facing notice functionality into something like WooCommerceRepository {@cwiseman 2021-06-03}
     *
     * @param EventContract $event
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return
            parent::shouldHandle($event)
            && ArrayHelper::contains(['checkout', 'both'], Configuration::get('payments.poynt.debugMode'));
    }
}
