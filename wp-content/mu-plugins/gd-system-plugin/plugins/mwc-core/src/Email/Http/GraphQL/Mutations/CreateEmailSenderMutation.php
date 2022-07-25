<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class CreateEmailSenderMutation extends AbstractGraphQLOperation
{
    /** {@inheritdoc} */
    protected $operation = 'mutation createEmailSender($emailAddress: String!, $siteId: ID!, $mailboxVerificationRedirectUrl: String!) {
  createEmailSender(input: {
    emailAddress: $emailAddress,
    siteId: $siteId,
    mailboxVerificationRedirectUrl: $mailboxVerificationRedirectUrl
  }) {
    id,
    createdAt,
    emailAddress,
    verifiedAt,
    verifiedBy,
    status
  }
}';

    /**
     * IssueTokenForSiteMutation constructor.
     */
    public function __construct()
    {
        $this->setAsMutation();
    }
}
