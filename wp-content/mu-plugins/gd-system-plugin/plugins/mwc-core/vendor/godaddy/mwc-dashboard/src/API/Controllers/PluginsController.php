<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedExtensionsRepository;
use GoDaddy\WordPress\MWC\Dashboard\Repositories\WooCommercePluginsRepository;
use WP_Error;
use WP_REST_Response;

/**
 * PluginsController controller class.
 */
class PluginsController extends AbstractController
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->route = 'shop/plugins';
    }

    /**
     * Registers the API routes for the plugins endpoint.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);
    }

    /**
     * Gets the schema for REST items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'plugin',
            'type'       => 'object',
            'properties' => [
                'slug'             => [
                    'description' => __("The plugin's slug.", 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'name'             => [
                    'description' => __("The plugin's name.", 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'managed' => [
                    'description' => __('Whether or not the plugin is a MWC managed plugin', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                ],
                'license'          => [
                    'description' => __("The plugin's WooCommerce.com subscription status.", 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'enum'        => ['active', 'expired', 'inactive', 'none'],
                    'readonly'    => true,
                ],
                'documentationUrl' => [
                    'description' => __('The plugin documentation URL', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }

    /**
     * Gets a REST response with all supported plugins information.
     *
     * @return WP_REST_Response|WP_Error
     *
     * @throws Exception
     */
    public function getItems()
    {
        // gets all the plugins that have support through the MWC Dashboard

        // all plugins included in the MWC platform
        $managedPlugins = ManagedExtensionsRepository::getManagedPlugins();
        // SkyVerge plugins installed independently
        $skyVergePlugins = WooCommercePluginsRepository::getWooCommerceSkyVergePlugins();

        $responsePluginsData = [];

        foreach ($managedPlugins as $plugin) {

            // only installed managed plugins are supported
            if (! $plugin->isInstalled()) {
                continue;
            }

            // params for querying the WooCommerce License and the documentation URL
            $pluginData = [
                '_product_id' => $plugin->getId(),
                'PluginURI'   => $plugin->getHomepageUrl(),
            ];

            $responsePluginsData[] = [
                'slug'             => $plugin->getSlug(),
                'name'             => $plugin->getName(),
                'managed'          => true,
                'license'          => WooCommercePluginsRepository::getWooCommerceLicense($pluginData),
                'documentationUrl' => ! empty($plugin->getDocumentationUrl()) ? $plugin->getDocumentationUrl() : WooCommercePluginsRepository::getDocumentationUrl($pluginData),
            ];
        }

        $managedPluginsSlugs = ArrayHelper::pluck($responsePluginsData, 'slug');

        foreach ($skyVergePlugins as $plugin) {

            // skip plugins that are already on the managed plugins list
            if (ArrayHelper::contains($managedPluginsSlugs, $plugin->getSlug())) {
                continue;
            }

            // params for querying the WooCommerce License and the documentation URL
            $pluginData = [
                '_product_id' => $plugin->getId(),
                'PluginURI'   => $plugin->getHomepageUrl(),
            ];

            $responsePluginsData[] = [
                'slug'             => $plugin->getSlug(),
                'name'             => $plugin->getName(),
                'managed'          => false,
                'license'          => WooCommercePluginsRepository::getWooCommerceLicense($pluginData),
                'documentationUrl' => ! empty($plugin->getDocumentationUrl()) ? $plugin->getDocumentationUrl() : WooCommercePluginsRepository::getDocumentationUrl($pluginData),
            ];
        }

        return rest_ensure_response([
            'plugins' => $responsePluginsData,
        ]);
    }
}
