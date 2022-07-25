<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WordPress;

use WP_Error;

/**
 * Repository handler for the WordPress HTTP component.
 */
class HttpRepository
{
    /**
     * Performs an HTTP remote request.
     *
     * @param string $url
     * @param array $args
     * @return array|WP_Error
     */
    public static function performRequest(string $url, array $args = [])
    {
        return wp_remote_request($url, $args);
    }

    /**
     * Retrieves the remote response status code.
     *
     * @param array|WP_Error $response
     * @return int|string
     */
    public static function getResponseCode($response)
    {
        return wp_remote_retrieve_response_code($response);
    }
}
