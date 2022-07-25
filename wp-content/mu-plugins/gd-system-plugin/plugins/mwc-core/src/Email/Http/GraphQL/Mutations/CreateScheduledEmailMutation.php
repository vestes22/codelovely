<?php

namespace GoDaddy\WordPress\MWC\Core\Email\Http\GraphQL\Mutations;

use GoDaddy\WordPress\MWC\Common\Http\GraphQL\AbstractGraphQLOperation;

class CreateScheduledEmailMutation extends AbstractGraphQLOperation
{
    /** @var string mutation operation. Refer to emails service API schema for input format. */
    protected $operation = 'mutation($input: CreateScheduledEmailInput!) { createScheduledEmail(input: $input) }';

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->setAsMutation();
    }
}
