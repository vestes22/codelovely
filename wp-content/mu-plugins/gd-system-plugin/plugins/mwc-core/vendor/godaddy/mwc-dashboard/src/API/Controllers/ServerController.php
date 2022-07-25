<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Http\Response;

/**
 * ServerController controller class.
 */
class ServerController extends AbstractController
{
    /** @var string Route */
    protected $route = 'disableSentry';

    /**
     * Registers the API routes for the endpoints provided by the controller.
     *
     * @NOTE: No permission check needed and do not want to expose schema.
     * In addition, the route is purposely breaking standard so it is a touch
     * less exposed.  This should be considered a hidden route for the client,
     * but does not require any protection as if it is disabled there is not
     * a security or functionality risk {JO 2021-03-03}.
     *
     * @since 1.2.0
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace, "/{$this->route}", [
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'disableSentry'],
                ],
            ]
        );
    }

    /**
     * Disable sentry server side for the duration of the Configuration cache.
     *
     * @since 1.2.0
     *
     * @return void
     * @throws Exception
     */
    public function disableSentry()
    {
        Configuration::set('reporting.sentry.enabled', false);

        (new Response())
            ->success(200)
            ->send();
    }

    /**
     * Gets the schema for REST items provided by the controller.
     *
     * @since 1.2.0
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [];
    }
}
