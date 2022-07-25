<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\ApplePayRequest;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * Apple Pay gateway.
 */
class ApplePayGateway extends AbstractGateway
{
    /**
     * Gets the domain association file contents based on environment.
     *
     * @return string
     * @throws Exception
     */
    public function getDomainAssociationFile() : string
    {
        $request = $this->getNewRequest('domain-association-file')
            ->setMethod('GET')
            ->setHeaders([
                'Content-Type' => 'text/plain',
            ]);

        $response = $this->doRequest($request);

        // get the raw response data since it won't be JSON
        return ArrayHelper::get($response->response, 'body', '');
    }

    /**
     * Registers the current site (new domain) with Apple Pay.
     *
     * @return Response
     * @throws Exception
     */
    public function register() : Response
    {
        $request = $this->getNewRequest('register')
            ->setMethod('POST')
            ->setBody([
                'businessId'   => Poynt::getBusinessId(),
                'domainNames'  => [SiteRepository::getDomain()],
                'merchantName' => SiteRepository::getTitle(),
                'merchantUrl'  => SiteRepository::getSiteUrl(), // optional parameter
            ]);

        return $this->doRequest($request);
    }

    /**
     * Unregisters the current site (domain) from Apple Pay.
     *
     * @return Response
     * @throws Exception
     */
    public function unregister() : Response
    {
        $request = $this->getNewRequest('unregister')
            ->setMethod('POST')
            ->setBody([
                'businessId'  => Poynt::getBusinessId(),
                'domainNames' => [SiteRepository::getDomain()],
                // @TODO an optional 'reason' (string) parameter can be added here {unfulvio 2021-11-24}
            ]);

        return $this->doRequest($request);
    }

    /**
     * Gets a new Apple Pay request for a given endpoint.
     *
     * @param string $endpoint
     * @return ApplePayRequest
     * @throws Exception
     */
    protected function getNewRequest(string $endpoint) : ApplePayRequest
    {
        return new ApplePayRequest($endpoint);
    }
}
