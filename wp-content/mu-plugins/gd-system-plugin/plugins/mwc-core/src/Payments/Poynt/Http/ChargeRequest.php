<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * The representation of a charge business request.
 *
 * @since 2.10.0
 */
class ChargeRequest extends AbstractBusinessRequest
{
    /**
     * ChargeRequest constructor.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('POST');
        $this->route = 'cards/tokenize/charge';

        parent::__construct();
    }
}
