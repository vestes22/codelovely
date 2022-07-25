<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

interface GraphQLOperationContract
{
    /**
     * Gets GraphQL operation, such as query or mutation, including selection fields and argument definition.
     *
     * @return string
     */
    public function getOperation() : string;

    /**
     * Gets GraphQL operation type. e.g., 'query', 'mutation', or 'subscription'.
     *
     * @return string
     */
    public function getOperationType() : string;

    /**
     * Gets associative array of variables for GraphQL operation.
     *
     * @return array
     */
    public function getVariables() : array;

    /**
     * Sets associative array of variables for GraphQL operation.
     *
     * @param array $variables
     * @return self
     */
    public function setVariables(array $variables);
}
