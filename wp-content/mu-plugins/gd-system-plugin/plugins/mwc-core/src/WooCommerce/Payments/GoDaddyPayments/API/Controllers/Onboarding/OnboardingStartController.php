<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\API\Controllers\Onboarding;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Producers\OnboardingEventsProducer;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\API\Controllers\AbstractController;
use WP_Error;
use WP_REST_Response;

/**
 * Class OnboardingStartController.
 */
class OnboardingStartController extends AbstractController
{
    /**
     * OnboardingStatusController constructor.
     */
    public function __construct()
    {
        $this->route .= '/onboarding/start';
    }

    /**
     * Registers the routes.
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace, "/{$this->route}", [
                [
                    'methods'             => 'POST', // \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'updateItem'],
                    'permission_callback' => [$this, 'updateItemPermissionsCheck'],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Begins the onboarding process.
     *
     * @return WP_Error|WP_REST_Response
     * @throws Exception
     */
    public function updateItem()
    {
        if (! $serviceId = Poynt::getServiceId()) {
            $serviceId = StringHelper::generateUuid4();
        }

        if (! $webhookSecret = Onboarding::getWebhookSecret()) {
            $webhookSecret = StringHelper::generateUuid4();
        }

        $this->createConnection($serviceId, $webhookSecret);

        Poynt::setServiceId($serviceId);
        Onboarding::setWebhookSecret($webhookSecret);

        Onboarding::setStatus(Onboarding::STATUS_INCOMPLETE);

        // TODO: broadcast an event {@cwiseman 2021-05-20}

        return rest_ensure_response([
            'serviceId' => Poynt::getServiceId(),
            'redirectNonce' => wp_create_nonce(OnboardingEventsProducer::ACTION_REDIRECT),
        ]);
    }

    /**
     * Creates the connection in the MWC API.
     *
     * @param string $serviceId
     * @param string $webhookSecret
     * @throws Exception
     */
    private function createConnection(string $serviceId, string $webhookSecret)
    {
        $requestUrl = ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.api.productionRoot', '') : Configuration::get('payments.api.stagingRoot', '');

        $request = new Request(StringHelper::trailingSlash($requestUrl).'onboarding/start');
        $request->body([
            'serviceId'     => $serviceId,
            'siteUrl'       => SiteRepository::getHomeUrl(),
            'siteName'      => SiteRepository::getTitle(),
            'siteXid'       => ManagedWooCommerceRepository::getXid(),
            'webhookSecret' => $webhookSecret,
        ])
            ->headers([
                'X-Site-Token'  => Configuration::get('godaddy.site.token', ''),
                'X-Account-UID' => Configuration::get('godaddy.account.uid', ''),
            ])
            ->setMethod('POST');

        $response = $request->send();

        if (201 !== $response->getStatus() || $response->isError()) {
            throw new Exception('Could not create connection');
        }
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
                'serviceId' => [
                    'description' => __('The GoDaddy Payments service ID', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'redirectNonce' => [
                    'description' => __('The nonce for validating the redirect after onboarding', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
