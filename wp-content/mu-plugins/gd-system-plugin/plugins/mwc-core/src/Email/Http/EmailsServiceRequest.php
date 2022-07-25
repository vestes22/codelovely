<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Response;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceAuthProviderException;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\IssueTokenForSiteMutation;
use GoDaddy\WordPress\MWC\Core\Email\Http\Providers\EmailsServiceAuthProvider;

class EmailsServiceRequest
{
    /* @var GraphQLOperationContract GraphQL operation that will be used in request. */
    protected $operation;

    /**
     * @param GraphQLOperationContract $operation
     * @return EmailsServiceRequest
     */
    public function setOperation(GraphQLOperationContract $operation) : EmailsServiceRequest
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Sends request and may retry with refreshed token if response is unauthenticated.
     *
     * @return Response
     * @throws Exception
     */
    public function send() : Response
    {
        return $this->maybeRetryIfUnauthenticated($this->sendWithoutRetry());
    }

    /**
     * Sends request without retry.
     *
     * @return Response
     * @throws EmailsServiceAuthProviderException
     * @throws Exception
     */
    protected function sendWithoutRetry() : Response
    {
        $request = (new Request($this->operation))
            ->setUrl($this->getApiUrl());

        if ($this->shouldAuth()) {
            $request->setHeaders(['Authorization' => $this->getAuthorizationHeaderValue()]);
        }

        return $request->send();
    }

    /**
     * Checks response and, if response is unauthenticated and retries are allowed, may retry with refreshed token.
     *
     * @param Response $response
     * @return Response
     * @throws EmailsServiceAuthProviderException
     */
    protected function maybeRetryIfUnauthenticated(Response $response) : Response
    {
        if ($this->shouldAuth() && $this->isResponseUnauthenticatedError($response)) {
            $this->getAuthProvider()->forget();

            return $this->sendWithoutRetry();
        }

        return $response;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getApiUrl() : string
    {
        return Configuration::get('mwc.emails_service.api.url', '');
    }

    /**
     * Does this operation require an authorization header with token?
     *
     * @return bool
     */
    protected function shouldAuth() : bool
    {
        return ! is_a($this->operation, IssueTokenForSiteMutation::class);
    }

    /**
     * Get Authorization header value with token for the request.
     *
     * @return string
     * @throws EmailsServiceAuthProviderException
     */
    protected function getAuthorizationHeaderValue() : string
    {
        return "Bearer {$this->getAuthProvider()->get()}";
    }

    /**
     * @return EmailsServiceAuthProvider
     */
    protected function getAuthProvider() : EmailsServiceAuthProvider
    {
        return new EmailsServiceAuthProvider();
    }

    /**
     * Is the given response from the service telling us it could not authenticate?
     *
     * @param Response $response
     * @return bool
     */
    protected function isResponseUnauthenticatedError(Response $response) : bool
    {
        $errorMessage = $response->getErrorMessage();

        return $errorMessage !== null && StringHelper::startsWith($errorMessage, 'Unauthenticated');
    }
}
