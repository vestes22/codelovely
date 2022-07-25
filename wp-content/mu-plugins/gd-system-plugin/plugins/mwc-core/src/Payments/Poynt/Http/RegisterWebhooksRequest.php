<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * Class RegisterWebhooksRequest.
 */
class RegisterWebhooksRequest extends Request
{
    /**
     * RegisterWebhooksRequest constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('POST');
        $this->route = 'hooks';

        parent::__construct();
    }
}
