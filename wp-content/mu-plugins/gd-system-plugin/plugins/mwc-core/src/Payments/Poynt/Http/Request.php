<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request as CommonRequest;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\AccessTokenGateway;

/**
 * Poynt API base request class.
 *
 * @since 2.10.0
 */
class Request extends CommonRequest
{
    /** @var string request route */
    protected $route = '';

    /**
     * Request constructor.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->setUserAgentHeader()
            ->sslVerify()
            ->timeout()
            ->setFullUrl();
    }

    /**
     * Sends the request.
     *
     * @return Response
     * @throws Exception
     */
    public function send() : Response
    {
        $this->setNewAccessToken()
            ->setAuthorizationHeader();

        return parent::send();
    }

    /**
     * Sets a new access token for the request.
     *
     * @return Request
     * @throws Exception
     */
    protected function setNewAccessToken() : Request
    {
        $accessToken = $this->getAccessTokenGateway()->generateToken();

        Configuration::set('payments.poynt.api.token', $accessToken);

        update_option('mwc_payments_poynt_api_token', $accessToken);

        return $this;
    }

    /**
     * Sets the Authorization bearer request header.
     *
     * @since 2.10.0
     *
     * @return self
     *
     * @throws Exception
     */
    protected function setAuthorizationHeader() : Request
    {
        if ($bearerToken = Configuration::get('payments.poynt.api.token')) {
            $this->headers = ArrayHelper::combine($this->headers, ['Authorization' => "Bearer {$bearerToken}"]);
        }

        return $this;
    }

    /**
     * Sets the user agent request header.
     *
     * @since 2.10.0
     *
     * @return self
     *
     * @throws Exception
     */
    protected function setUserAgentHeader() : Request
    {
        $this->headers = ArrayHelper::combine((array) $this->headers, [
            'Accept' => 'application/json',
            'Api-Version' => '1.2',
            'Content-Type' => 'application/json',
            'Poynt-Request-Id' => wp_generate_uuid4(),
            'User-Agent' => 'GoDaddy-Payments-for-MWP',
        ]);

        return $this;
    }

    /**
     * Sets the request full URL.
     *
     * @since 2.10.0
     *
     * @return self
     *
     * @throws Exception
     */
    protected function setFullUrl() : Request
    {
        $this->url = StringHelper::endWith($this->getRootUrl(), '/').$this->route;

        return $this;
    }

    /**
     * Gets the API root URL, depending on environment.
     *
     * @return string
     * @throws Exception
     */
    public function getRootUrl() : string
    {
        return (string) ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.poynt.api.productionRoot', '') : Configuration::get('payments.poynt.api.stagingRoot', '');
    }

    /**
     * Gets an instance of AccessTokenGateway.
     *
     * @since 2.13.0
     *
     * @return AccessTokenGateway
     */
    protected function getAccessTokenGateway() : AccessTokenGateway
    {
        return new AccessTokenGateway();
    }
}
