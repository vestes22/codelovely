<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Store devices for businesses request.
 *
 * @since 2.10.0
 */
class StoreDevicesRequest extends AbstractBusinessRequest
{
    /** @var string request route */
    protected $route = 'stores';

    /**
     * StoreDevicesRequest constructor.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('GET');
        parent::__construct();
    }
}
