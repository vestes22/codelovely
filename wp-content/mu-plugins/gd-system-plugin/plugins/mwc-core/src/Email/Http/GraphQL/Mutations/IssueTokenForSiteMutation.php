<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class IssueTokenForSiteMutation extends AbstractGraphQLOperation
{
    /** {@inheritdoc} */
    protected $operation = 'mutation issueSiteToken($siteId: ID!, $uid: ID!, $siteToken: ID!) {issueTokenForSite(siteId: $siteId, uid: $uid, siteToken: $siteToken)}';

    /**
     * IssueTokenForSiteMutation constructor.
     */
    public function __construct()
    {
        $this->setAsMutation();
    }
}
