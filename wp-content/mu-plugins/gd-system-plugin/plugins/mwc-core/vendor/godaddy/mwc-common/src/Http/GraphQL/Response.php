<?php

namespace GoDaddy\WordPress\MWC\Common\Http\GraphQL;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response as BaseResponse;
use WP_Error;

/**
 * GraphQL Response handler.
 */
class Response extends BaseResponse
{
    /**
     * GraphQL response constructor.
     *
     * @param array|WP_Error|null $response
     */
    public function __construct($response = null)
    {
        parent::__construct($response);

        if ($response && ! parent::isError($response)) {
            $this->setGraphQLBody($response);
        }
    }

    /**
     * Sets the parsed GraphQL response body.
     *
     * @param array $response
     */
    protected function setGraphQLBody(array $response)
    {
        $this->body = $this->parseResponseBody($response);
    }

    /**
     * Parses the GraphQL response body.
     *
     * @param array $response
     * @return array|null
     */
    protected function parseResponseBody(array $response)
    {
        return json_decode(ArrayHelper::get($response, 'body'), true);
    }

    /**
     * Determines if the GraphQL response is an error response.
     *
     * @param object|array|WP_Error|null $response
     * @return bool
     */
    public function isError($response = null) : bool
    {
        if (parent::isError($response)) {
            return true;
        }

        $body = $response ? $this->parseResponseBody($response) : $this->body;

        return ArrayHelper::exists($body, 'errors');
    }

    /**
     * Gets the GraphQl errors from the response.
     *
     * @param WP_Error|array|null $response
     * @return array|null
     */
    protected function getErrors($response = null)
    {
        $responseWithErrors = $response ?: $this->response;
        if (is_wp_error($responseWithErrors)) {
            return $responseWithErrors->get_error_messages();
        }

        $body = $response ? $this->parseResponseBody($response) : $this->body;

        return $body ? ArrayHelper::get($body, 'errors') : null;
    }

    /**
     * Gets the error message from the GraphQl error.
     *
     * @return string|null
     */
    public function getErrorMessage()
    {
        if ($errors = $this->getErrors()) {
            $error = array_shift($errors);

            if (ArrayHelper::has($error, 'message')) {
                $error = ArrayHelper::get($error, 'message');
            }

            if (is_string($error)) {
                return $error;
            }
        }

        return null;
    }
}
