<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Http\Request;

/**
 * Request handler for performing requests to GoDaddy.
 *
 * This class also wraps a Managed WooCommerce Site Token required by GoDaddy requests.
 *
 * @since 1.0.0
 */
class MessagesRequest extends Request
{
    /** @var string managed WooCommerce auth token */
    public $authToken;

    /** @var string managed WooCommerce auth token type */
    public $authTokenType;

    /** @var string managed WooCommerce site token */
    public $siteToken;

    /**
     * Constructor.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAuthToken()
            ->setAuthTokenType()
            ->setSiteToken()
            ->headers([
                'Authorization' => "{$this->authTokenType} {$this->authToken}",
                'X-Site-Token' => $this->siteToken,
            ]);
    }

    /**
     * Sets the current site Auth token.
     *
     * @since 1.0.0
     *
     * @param string|null $token
     * @return MessagesRequest
     * @throws Exception
     */
    public function setAuthToken($token = null) : MessagesRequest
    {
        $this->authToken = $token ?: Configuration::get('messages.api.auth.token', 'empty');

        return $this;
    }

    /**
     * Sets the current site Auth token type.
     *
     * @since 1.0.0
     *
     * @param string|null $type
     * @return MessagesRequest
     * @throws Exception
     */
    public function setAuthTokenType($type = null) : MessagesRequest
    {
        $this->authTokenType = $type ?: Configuration::get('messages.api.auth.type', 'Bearer');

        return $this;
    }

    /**
     * Sets the current site API request token.
     *
     * @since 1.0.0
     *
     * @param string|null $token
     * @return MessagesRequest
     * @throws Exception
     */
    public function setSiteToken($token = null) : MessagesRequest
    {
        $this->siteToken = $token ?: Configuration::get('godaddy.site.token', 'empty');

        return $this;
    }
}
