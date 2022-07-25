<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http\Providers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Email\Cache\Types\CacheEmailsServiceToken;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailsServiceAuthProviderException;
use GoDaddy\WordPress\MWC\Core\Email\Http\EmailsServiceRequest;
use GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations\IssueTokenForSiteMutation;

class EmailsServiceAuthProvider
{
    /**
     * Attempts to retrieve cached token. Otherwise, will request a new token.
     *
     * @return string
     * @throws EmailsServiceAuthProviderException
     */
    public function get() : string
    {
        return CacheEmailsServiceToken::getInstance()->get() ?? $this->requestToken();
    }

    /**
     * Clears the cached token.
     *
     * @return EmailsServiceAuthProvider self
     */
    public function forget() : EmailsServiceAuthProvider
    {
        CacheEmailsServiceToken::getInstance()->clear();

        return $this;
    }

    /**
     * Requests a new token from the Emails Service.
     *
     * @return string
     * @throws EmailsServiceAuthProviderException
     * @throws Exception
     */
    protected function requestToken() : string
    {
        $response = $this->getEmailsServiceRequest()->send();

        if ($response->isError() || empty($token = ArrayHelper::get($response->getBody(), 'data.issueTokenForSite'))) {
            throw new EmailsServiceAuthProviderException("API responded with error: {$response->getErrorMessage()}");
        }

        CacheEmailsServiceToken::getInstance()->set($token);

        return $token;
    }

    /**
     * Gets a proper Emails Service request instance to issue site token.
     *
     * @return EmailsServiceRequest
     * @throws Exception
     */
    protected function getEmailsServiceRequest() : EmailsServiceRequest
    {
        return (new EmailsServiceRequest())->setOperation($this->getIssueTokenForSiteMutation());
    }

    /**
     * Gets an issue site token GraphQL mutation operation.
     *
     * @return IssueTokenForSiteMutation
     * @throws Exception
     */
    protected function getIssueTokenForSiteMutation() : IssueTokenForSiteMutation
    {
        return (new IssueTokenForSiteMutation())
            ->setVariables([
                'siteId'    => ManagedWooCommerceRepository::getSiteId(),
                'uid'       => Configuration::get('godaddy.account.uid'),
                'siteToken' => Configuration::get('godaddy.site.token', 'empty'),
            ]);
    }
}
