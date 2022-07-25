<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\AccessTokenAdapter;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Payments\Traits\AdaptsRequestsTrait;

class AccessTokenGateway extends AbstractGateway
{
    use AdaptsRequestsTrait;

    /**
     * Generates a new access token.
     *
     * @return string
     * @throws Exception
     */
    public function generateToken() : string
    {
        $existingToken = Configuration::get('payments.poynt.api.token');

        return $this->doAdaptedRequest($existingToken, new AccessTokenAdapter($existingToken));
    }
}
