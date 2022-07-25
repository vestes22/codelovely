<?php

namespace GoDaddy\WordPress\MWC\Common\Http\GraphQL;

use GoDaddy\WordPress\MWC\Common\Contracts\GraphQLOperationContract;

abstract class AbstractGraphQLOperation implements GraphQLOperationContract
{
    /** @var string GraphQL operation */
    protected $operation;

    /**
     * @var string GraphQL's operation type
     */
    protected $operationType = 'query';

    /**
     * @var array Associative array of GraphQL operation variables.
     */
    protected $variables = [];

    /**
     * Gets GraphQL operation, such as query or mutation, including selection fields and argument definition.
     *
     * @return string
     */
    public function getOperation() : string
    {
        return $this->operation;
    }

    /**
     * Gets GraphQL operation type. e.g., 'query', 'mutation', or 'subscription'.
     *
     * @return string
     */
    public function getOperationType() : string
    {
        return $this->operationType;
    }

    /**
     * Gets associative array of variables for GraphQL operation.
     *
     * @return array
     */
    public function getVariables() : array
    {
        return $this->variables;
    }

    /**
     * Sets operationType to mutation. Concrete class for a mutation is encouraged to call this in constructor.
     *
     * @return AbstractGraphQLOperation
     */
    protected function setAsMutation() : AbstractGraphQLOperation
    {
        $this->operationType = 'mutation';

        return $this;
    }

    /**
     * Sets associative array of variables for GraphQL operation.
     *
     * @param array $variables
     * @return AbstractGraphQLOperation
     */
    public function setVariables(array $variables) : AbstractGraphQLOperation
    {
        $this->variables = $variables;

        return $this;
    }
}
