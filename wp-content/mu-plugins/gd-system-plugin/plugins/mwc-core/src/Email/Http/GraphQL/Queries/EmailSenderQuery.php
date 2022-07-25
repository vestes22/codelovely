<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Queries;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class EmailSenderQuery extends AbstractGraphQLOperation
{
    /** {@inheritdoc} */
    protected $operation = 'query EmailSenderQuery($emailAddress: String!) {
  emailSender(emailAddress: $emailAddress) {
    id,
    emailAddress,
    verifiedAt,
    verifiedBy,
    status
  }
}';
}
