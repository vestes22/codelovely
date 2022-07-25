<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Dashboard\Users\Permissions\ShowExtensionsRecommendationsPermission;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * User controller class.
 */
class UserController extends AbstractController
{
    /**
     * Controller constructor.
     *
     * @since 1.2.0
     */
    public function __construct()
    {
        $this->route = 'me';
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     *
     * @since 1.2.0
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            [
                'methods'             => 'PUT', // WP_REST_Server::CREATABLE
                'callback'            => [$this, 'updateItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
                'args'                => $this->getItemSchemaProperties(),
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);
    }

    /**
     * Gets the schema.
     *
     * @since 1.2.0
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'user',
            'type'       => 'object',
            'properties' => [
                'user' => [
                    'description' => __('The current user information.', 'mwc-dashboard'),
                    'type'        => 'object',
                    'properties'  => $this->getItemSchemaProperties(),
                    'context'     => ['view', 'edit'],
                ],
            ],
        ];
    }

    /**
     * Gets the schema properties.
     *
     * @since 1.2.0
     *
     * @return array
     */
    protected function getItemSchemaProperties() : array
    {
        return [
            'id' => [
                'description' => __('The ID of the current user.', 'mwc-dashboard'),
                'type'        => 'int',
                'context'     => ['view', 'edit'],
                'readonly'    => true,
            ],
            'marketingPermissions' => [
                'description' => __('The marketing permissions.', 'mwc-dashboard'),
                'type'        => 'object',
                'properties'  => [
                    'SHOW_EXTENSIONS_RECOMMENDATIONS' => [
                        'description' => __('Whether to show extensions recommendations.', 'mwc-dashboard'),
                        'type'        => 'bool',
                        'context'     => ['view', 'edit'],
                    ],
                ],
                'context'     => ['view', 'edit'],
            ],
        ];
    }

    /**
     * Gets the item.
     *
     * @since 1.2.0
     *
     * @return WP_Error|WP_REST_Response
     */
    public function getItem()
    {
        return rest_ensure_response($this->prepareItem(User::getCurrent()->getId()));
    }

    /**
     * Updates the item.
     *
     * @since 1.2.0
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     */
    public function updateItem(WP_REST_Request $request)
    {
        // @TODO: When the other issues are fixed we should just be passing a User Object here -- will condense everything further {JO: 2021-03-26}
        $userId = User::getCurrent()->getId();
        $showRecommendations = ArrayHelper::get($request->get_json_params(), 'marketingPermissions.SHOW_EXTENSIONS_RECOMMENDATIONS');

        // @TODO: Why the is_null?  Looks like we only care if this is true or yes? {JO: 2021-03-26}
        if (! is_null($showRecommendations)) {
            $permission = $this->getShowExtensionsRecommendationsPermission($userId);

            if (true === $showRecommendations || 'yes' === $showRecommendations) {
                $permission->allow();
            } else {
                $permission->disallow();
            }
        }

        // @TODO: Should use our own response class so error tracking and what not works as expected {JO: 2021-03-26}
        return rest_ensure_response($this->prepareItem($userId));
    }

    /**
     * Prepares the item.
     *
     * @since 1.2.0
     *
     * @param int $userId
     *
     * @return array a list of marketing permissions
     */
    protected function prepareItem(int $userId) : array
    {
        $permission = $this->getShowExtensionsRecommendationsPermission($userId);

        return [
            'user' => [
                'id'                   => $userId,
                'marketingPermissions' => [
                    'SHOW_EXTENSIONS_RECOMMENDATIONS' => $permission->isAllowed(),
                ],
            ],
        ];
    }

    /**
     * Gets the ShowExtensionsRecommendationsPermission instance for the given user id.
     *
     * @since 1.2.0
     *
     * @param int $userId
     * @return ShowExtensionsRecommendationsPermission
     */
    protected function getShowExtensionsRecommendationsPermission(int $userId): ShowExtensionsRecommendationsPermission
    {
        // @TODO: See notes in ShowExtensionsRecommendationsPermission -- This method shouldn't exist at all {JO: 2021-03-26}
        return new ShowExtensionsRecommendationsPermission($userId);
    }
}
