<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Shipping;

use BadMethodCallException;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Providers\Contracts\ProviderContract;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\Traits\RequiresWooCommercePermissionsTrait;
use GoDaddy\WordPress\MWC\Shipping\Shipping;
use WP_REST_Request;

class ProvidersController extends AbstractController
{
    use RequiresWooCommercePermissionsTrait;

    /** @var string route */
    protected $route = 'shipping-providers';

    /**
     * Registers the API routes for the endpoints provided by the controller.
     *
     * @since x.y.z
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
            'args' => [
                'query' => [
                    'required'          => false,
                    'type'              => 'string',
                    'validate_callback' => 'rest_validate_request_arg',
                    'sanitize_callback' => 'rest_sanitize_request_arg',
                ],
            ],
            'schema' => [$this, 'getItemSchema'],
        ]);
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @since x.y.z
     *
     * @return array
     */
    public function getItemSchema(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'shipping-providers',
            'type'    => 'array',
            'items'   => [
                'type'       => 'object',
                'properties' => [
                    'label'        => [
                        'description' => __('The shipping provider label.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view'],
                        'readonly'    => true,
                    ],
                    'name' => [
                        'description' => __('The shipping provider name.', 'mwc-dashboard'),
                        'type'        => 'string',
                        'context'     => ['view'],
                        'readonly'    => true,
                    ],
                    'trackingUrl' => [
                        'description' => __('The shipping provider tracking URL format.', 'mwc-dashboard'),
                        'type'        => ['string', 'null'],
                        'context'     => ['view'],
                        'readonly'    => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Gets a REST response with all of the shipping providers.
     *
     * @since x.y.z
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function getItems(WP_REST_Request $request)
    {
        $providers = Shipping::getInstance()->getProviders();
        $responseData = [];

        foreach ($providers as $provider) {
            $responseData[] = [
                'label' => $provider->getLabel(),
                'name' => $provider->getName(),
                'trackingUrl' => $this->getTrackingUrlTemplate($provider),
            ];
        }

        $responseData[] = [
            'label' => 'Other',
            'name'  => 'other',
            'trackingUrl' => null,
        ];

        (new Response)
            ->body(['shippingProviders' => $responseData])
            ->success(200)
            ->send();
    }

    /**
     * Returns the tracking URL template for the given provider, if any.
     *
     * @since x.y.z
     *
     * @param ProviderContract $provider
     * @return string | null
     */
    protected function getTrackingUrlTemplate(ProviderContract $provider)
    {
        try {
            $tracking = $provider->tracking();
        } catch (BadMethodCallException $e) {
            return null;
        }

        if (! is_callable([$tracking, 'getTrackingUrlTemplate'])) {
            return null;
        }

        return $tracking->getTrackingUrlTemplate();
    }
}
