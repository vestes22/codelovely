<?php

namespace GoDaddy\WordPress\MWC\Common\Events\Contracts;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;

/**
 * Producer contract.
 */
interface ProducerContract extends ComponentContract
{
    /**
     * Setups the events producer.
     *
     * @deprecated
     */
    public function setup();
}
