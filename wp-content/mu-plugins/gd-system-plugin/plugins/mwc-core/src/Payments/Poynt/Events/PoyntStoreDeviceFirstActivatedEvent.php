<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Core\Payments\Models\StoreDevice;

/**
 * Represents the first time a store device is activated.
 *
 * TODO: refactor this to be a StoreDeviceFirstActivatedEvent, once the StoreDevice class is really generic and we have a proper way to differentiate a Poynt device from other devices {dmagalhaes 2021-10-12}
 */
class PoyntStoreDeviceFirstActivatedEvent implements EventContract
{
    /** @var StoreDevice */
    protected $device;

    /**
     * Event constructor.
     *
     * @param StoreDevice $device
     */
    public function __construct(StoreDevice $device)
    {
        $this->device = $device;
    }
}
