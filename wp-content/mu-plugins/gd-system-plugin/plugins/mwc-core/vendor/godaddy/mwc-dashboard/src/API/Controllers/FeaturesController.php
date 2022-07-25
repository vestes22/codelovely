<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use WP_REST_Request;

defined('ABSPATH') or exit;

/**
 * Features controller class.
 */
class FeaturesController extends AbstractController
{
    use RequiresWooCommercePermissionsTrait;

    /**
     * Route.
     *
     * @var string
     */
    protected $route = 'features';

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace,
            "/{$this->route}",
            [
                [
                    'methods'             => 'GET',
                    'callback'            => [$this, 'getItems'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Gets a REST response with the native features visible to the site admin.
     */
    public function getItems(WP_REST_Request $request)
    {
        try {
            $allFeatures = Configuration::get('features');
            $features = [];

            foreach ($allFeatures as $feature) {
                // skip features without the name set (should not be displayed)
                if (empty($name = ArrayHelper::get($feature, 'name'))) {
                    continue;
                }

                $features[$name] = $this->prepareItem($feature);
            }

            // sort alphabetically by name
            ksort($features);

            $responseData = ['features' => array_values($features)];

            (new Response)
                ->body($responseData)
                ->success(200)
                ->send();
        } catch (BaseException $exception) {
            (new Response)
                ->error([$exception->getMessage()], $exception->getCode())
                ->send();
        }
    }

    /**
     * Prepares the given feature data for API response.
     *
     * @param array $feature
     * @return array
     */
    protected function prepareItem(array $feature) : array
    {
        return [
            'name'             => ArrayHelper::get($feature, 'name'),
            'description'      => ArrayHelper::get($feature, 'description', ''),
            'documentationUrl' => $this->getDocumentationUrl(ArrayHelper::get($feature, 'documentation_url', '')),
            'settingsUrl'      => ArrayHelper::get($feature, 'settings_url', ''),
            'categories'       => ArrayHelper::get($feature, 'categories', []),
            'enabled'          => ArrayHelper::get($feature, 'enabled', false),
        ];
    }

    /**
     * Gets the documentation URL, modified for resellers, if applicable.
     *
     * @param string $originalUrl
     * @return string
     */
    protected function getDocumentationUrl(string $originalUrl) : string
    {
        if (empty($originalUrl) || ! ManagedWooCommerceRepository::isReseller()) {
            return $originalUrl;
        }

        if (StringHelper::contains($originalUrl, '/godaddy.com/')) {
            $url = StringHelper::replaceFirst($originalUrl, '/godaddy.com/', '/www.secureserver.net/');
        } else {
            $url = $originalUrl;
        }

        if (! StringHelper::contains($url, '/www.secureserver.net/')) {
            return $url;
        }

        // append private label id
        $privateLabelId = ManagedWooCommerceRepository::getResellerId();
        if ($privateLabelId) {
            $url .= StringHelper::contains($url, '?') ? '&' : '?';
            $url .= "pl_id=$privateLabelId";
        }

        return $url;
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema(): array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'feature',
            'type'       => 'object',
            'properties' => [
                'name'             => [
                    'description' => __('The native feature name.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'description'      => [
                    'description' => __('The native feature description.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'documentationUrl' => [
                    'description' => __('The native feature documentation URL.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'settingsUrl'      => [
                    'description' => __('The native feature settings URL, if applicable.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'categories'       => [
                    'description' => __('The native feature categories.', 'mwc-dashboard'),
                    'type'        => 'array',
                    'items'       => [
                        'type'     => 'string',
                        'enum'     => [
                            'Cart and Checkout',
                            'Marketing and Messaging',
                            'Merchandising',
                            'Payments',
                            'Product Type',
                            'Shipping',
                            'Store Management',
                        ],
                        'context'  => ['view', 'edit'],
                        'readonly' => true,
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'enabled'          => [
                    'description' => __('Whether or not the native feature is enabled for this site.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
