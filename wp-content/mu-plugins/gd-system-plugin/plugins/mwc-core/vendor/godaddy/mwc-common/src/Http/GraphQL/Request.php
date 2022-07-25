<?php

namespace GoDaddy\WordPress\MWC\Common\Http\GraphQL;

use Exception;
use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;
use GoDaddy\WordPress\MWC\Common\Http\Request as BaseRequest;
use GoDaddy\WordPress\MWC\Common\Http\Response as BaseResponse;
use stdClass;

/**
 * GraphQL Request handler.
 */
class Request extends BaseRequest
{
    /** @var array allowed request method types */
    protected $allowedMethodTypes = ['POST'];

    /** @var string default allowed method */
    protected $defaultAllowedMethod = 'post';

    /** @var GraphQLOperationContract operation class */
    protected $operation;

    /** @var stdClass the type of response the request should return */
    protected $responseClass = Response::class;

    /**
     * GraphQL request constructor.
     *
     * Require a GraphQLOperations contract so we can get the query and variables.
     *
     * @throws Exception
     */
    public function __construct(GraphQLOperationContract $operation)
    {
        $this->operation = $operation;

        parent::__construct();
    }

    /**
     * Override: GraphQL Requests should not contain query parameters.
     *
     * @return string
     * @throws Exception
     */
    protected function buildUrlString() : string
    {
        return $this->url;
    }

    /**
     * Sends the response.
     *
     * Resets the body to be valid GraphQL syntax then calls parent method.
     * Requires that a valid GraphQL body string be passed in for now.
     *
     * @return Response|BaseResponse
     * @throws Exception
     */
    public function send() : BaseResponse
    {
        $this->setBody([
            'query'     => $this->operation->getOperation(),
            'variables' => $this->operation->getVariables(),
        ]);

        return parent::send();
    }
}
