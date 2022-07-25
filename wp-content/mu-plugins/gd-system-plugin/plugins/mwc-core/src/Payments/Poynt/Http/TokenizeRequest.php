<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * The representation of a tokenize business request.
 *
 * @since 2.10.0
 */
class TokenizeRequest extends AbstractBusinessRequest
{
    /**
     * TokenizeRequest constructor.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setMethod('POST');
        $this->route = 'cards/tokenize';

        parent::__construct();
    }
}
