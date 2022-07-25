<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\API\Controllers\Onboarding;

use Exception;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\API\Controllers\AbstractController;
use WP_Error;
use WP_REST_Response;

/**
 * Class OnboardingStatusController.
 */
class OnboardingStatusController extends AbstractController
{
    /**
     * OnboardingStatusController constructor.
     */
    public function __construct()
    {
        $this->route .= '/onboarding/status';
    }

    /**
     * Registers the routes.
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace, "/{$this->route}", [
                [
                    'methods'             => 'GET', // \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getItem'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Gets the status.
     *
     * @return WP_Error|WP_REST_Response
     * @throws Exception
     */
    public function getItem()
    {
        return rest_ensure_response([
            'status' => [
                'state' => Onboarding::getStatus(),
                'serviceId' => Poynt::getServiceId(),
            ],
        ]);
    }

    /**
     * Gets the item schema.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'status',
            'type'       => 'object',
            'properties' => [
                'state' => [
                    'description' => __('The GoDaddy Payments onboarding status', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'enum'        => [
                        Onboarding::STATUS_CONNECTED,
                        Onboarding::STATUS_CONNECTING,
                        Onboarding::STATUS_DECLINED,
                        Onboarding::STATUS_DISCONNECTED,
                        Onboarding::STATUS_INCOMPLETE,
                        Onboarding::STATUS_NEEDS_ATTENTION,
                        Onboarding::STATUS_PENDING,
                        Onboarding::STATUS_SUSPENDED,
                        Onboarding::STATUS_TERMINATED,
                    ],
                    'readonly'    => true,
                ],
                'serviceId' => [
                    'description' => __('The GoDaddy Payments service ID', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
