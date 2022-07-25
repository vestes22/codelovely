<?php

namespace GoDaddy\WordPress\MWC\Dashboard\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\TemplatesRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Dashboard\Repositories\UserRepository;
use GoDaddy\WordPress\MWC\Dashboard\Support\Support;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * ShopController controller class.
 */
class ShopController extends AbstractController
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->route = 'shop';
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace, "/{$this->route}", [
                [
                    'methods'             => 'GET',
                    /* @see WP_REST_Server::READABLE */
                    'callback'            => [$this, 'getItem'],
                    'permission_callback' => [$this, 'getItemsPermissionsCheck'],
                ],
                'schema' => [$this, 'getItemSchema'],
            ]
        );
    }

    /**
     * Gets the shop information.
     *
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItem()
    {
        $adminUser = User::getCurrent();
        $supportUser = null;

        if (Configuration::get('support.support_user.email')) {
            $supportUser = User::getByEmail(Configuration::get('support.support_user.email'));
        }

        if (! $supportUser && Configuration::get('support.support_user.login')) {
            $supportUser = User::getByHandle(Configuration::get('support.support_user.login'));
        }

        $item = [
            'shop' => [
                'siteId'                                   => ManagedWooCommerceRepository::getSiteId(),
                'siteURL'                                  => site_url(),
                'adminEmail'                               => $adminUser ? $adminUser->getEmail() : '',
                'firstAdminLoginTimestamp'                 => $this->getFirstAdminLoginTimestamp(),
                'supportUserEmail'                         => $supportUser ? $supportUser->getEmail() : '',
                'supportBotConnected'                      => Support::isSupportConnected(),
                'woocommerceConnected'                     => WooCommerceRepository::isWooCommerceConnected(),
                'dashboardType'                            => ManagedWooCommerceRepository::hasEcommercePlan() ? 'MWC' : '',
                'isReseller'                               => ManagedWooCommerceRepository::isReseller(),
                'privateLabelId'                           => ManagedWooCommerceRepository::getResellerId(),
                'supportBotConnectUrl'                     => Support::getConnectUrl(),
                'isCurrentUserOptedInForDashboardMessages' => UserRepository::userOptedInForDashboardMessages(),
                'createdAt'                                => $this->getShopCreatedAt(),
                'location'                                 => $this->getShopLocation(),
                'shouldRecommendGoDaddyPayments'           => $this->shouldRecommendGoDaddyPayments(),
                // @TODO Utilize core EmailNotifications::isActive() once merged into core {ssmith1 2021-09-07}
                'isEmailNotificationsFeatureActive'        => static::isEmailNotificationsFeatureActive(),
                // @TODO Utilize core EmailNotifications::isEnabled() once merged into core {ssmith1 2021-09-07}
                'isEmailNotificationsFeatureEnabled'       => static::isEmailNotificationsFeatureActive() && Configuration::get('email_notifications.enabled', false),
                'isEmailVerificationFeatureActive'         => static::isEmailVerificationFeatureActive(),
                'isOnlyVirtual'                            => $this->isSellingOnlyDigitalProducts(),
                'hasEmailTemplateOverrides'                => $this->hasEmailTemplateOverrides(),
                // @TODO Utilize core Onboarding::shouldLoad() once merged into core {nmolham 2021-11-17}
                'isOnboardingEnabled'                      => $this->isOnboardingEnabled(),
            ],
        ];

        return rest_ensure_response($item);
    }

    /**
     * Determines if the Onboarding feature is enabled or not.
     *
     * @return bool
     * @throws Exception
     */
    protected function isOnboardingEnabled() : bool
    {
        return Configuration::get('features.onboarding.enabled', false) &&
            ManagedWooCommerceRepository::isAllowedToUseNativeFeatures() &&
            ManagedWooCommerceRepository::hasEcommercePlan() &&
            WooCommerceRepository::isWooCommerceActive() &&
            current_user_can('manage_woocommerce') &&
            current_user_can('install_plugins') &&
            current_user_can('activate_plugins');
    }

    /**
     * Determines whether the Email Notifications feature is available.
     *
     * @TODO this is a duplicate of MWC Core EmailNotifications::isActive, which we can't use from the Dashboard until these projects have been merged {unfulvio 2021-10-25}
     *
     * @return bool
     * @throws Exception
     */
    public static function isEmailNotificationsFeatureActive() : bool
    {
        return Configuration::get('features.email_notifications', true)
            && ManagedWooCommerceRepository::hasEcommercePlan()
            && WooCommerceRepository::isWooCommerceActive()
            && ManagedWooCommerceRepository::isAllowedToUseNativeFeatures();
    }

    /**
     * Determines if email verification is active.
     *
     * @TODO Remove when rollout constraints are removed {JO: 2021-10-25}
     *
     * @return bool
     * @throws Exception
     */
    public static function isEmailVerificationFeatureActive() : bool
    {
        return static::isEmailNotificationsFeatureActive();
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'shop',
            'type'       => 'object',
            'properties' => [
                'siteId'                                   => [
                    'description' => __('Site ID.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'siteUrl'                                  => [
                    'description' => __('Site URL.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'adminEmail'                               => [
                    'description' => __('Current admin user\'s email.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'supportUserEmail'                         => [
                    'description' => __('Support user\'s email, if a support user exists.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'supportBotConnected'                      => [
                    'description' => __('Whether or not the site is connected to the support bot.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'woocommerceConnected'                     => [
                    'description' => __('Whether or not the site is connected to WooCommerce.com.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'dashboardType'                            => [
                    'description' => __('Dashboard type (MWC or empty).', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isReseller'                               => [
                    'description' => __('Whether or not the site is sold by a reseller.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'privateLabelId'                           => [
                    'description' => __('The reseller private label ID (1 means GoDaddy, so not a reseller).', 'mwc-dashboard'),
                    'type'        => 'int',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'supportBotConnectUrl'                     => [
                    'description' => __('URL to connect the site to the support bot.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isCurrentUserOptedInForDashboardMessages' => [
                    'description' => __('Whether or not the current user is opted in to receive MWC Dashboard messages.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'createdAt'                                => [
                    'description' => __('The Shop page\'s creation date.', 'mwc-dashboard'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'location'                                 => [
                    'type'       => 'object',
                    'properties' => [
                        'address1'   => [
                            'description' => __('Address line 1', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'address2'   => [
                            'description' => __('Address line 2', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'city'       => [
                            'description' => __('City', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'country'    => [
                            'description' => __('Country', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'state'      => [
                            'description' => __('State', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'postalCode' => [
                            'description' => __('Postal code', 'mwc-dashboard'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                    ],
                ],
                'isEmailNotificationsFeatureActive'        => [
                    'description' => __('Whether or not the site email notifications feature is active.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isEmailNotificationsFeatureEnabled'       => [
                    'description' => __('Whether or not the site email notifications feature is enabled.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'hasEmailTemplateOverrides'                => [
                    'description' => __('Whether the site is currently overriding any of the WooCommerce email templates.', 'mwc-dashboard'),
                    'type'        => 'bool',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }

    /**
     * Gets the timestamp for the first time that an administrator logged into the admin dashboard on the Managed WooCommerce platform.
     *
     * @return int
     */
    protected function getFirstAdminLoginTimestamp() : int
    {
        return (int) get_option('gd_system_first_login', 0);
    }

    /**
     * Gets the created at date for WooCommerce's shop page.
     *
     * @return string
     */
    private function getShopCreatedAt()
    {
        if (! function_exists('wc_get_page_id')) {
            return '';
        }

        if (! $shopPage = get_post(wc_get_page_id('shop'))) {
            return '';
        }

        return $shopPage->post_date;
    }

    /**
     * Gets the store location from WooCommerce settings.
     *
     * @return string
     */
    private function getShopLocation() : array
    {
        if (! function_exists('WC')) {
            return [];
        }

        return [
            'address1'   => WC()->countries->get_base_address(),
            'address2'   => WC()->countries->get_base_address_2(),
            'city'       => WC()->countries->get_base_city(),
            'country'    => WC()->countries->get_base_country(),
            'state'      => WC()->countries->get_base_state(),
            'postalCode' => WC()->countries->get_base_postcode(),
        ];
    }

    /*
     * Decides whether we should recommend GoDaddy Payments to the current site.
     * {llessa 2021-08-04} When we merge mwc-dashboard package into mwc-core, this method can be simplified to:
     *      GoDaddyPaymentsGateway::isActive() && empty(Poynt::getServiceId())
     *
     * @return bool
     */
    protected function shouldRecommendGoDaddyPayments() : bool
    {
        return ! ManagedWooCommerceRepository::isReseller() &&
            empty(Configuration::get('payments.poynt.serviceId')) &&
            WooCommerceRepository::getBaseCountry() == 'US' &&
            WooCommerceRepository::getCurrency() == 'USD';
    }

    /**
     * Determines whether the store is selling digiral products only.
     *
     * We consider that a store sells digital products only if the merchant didn't
     * select physical_goods as one of the features during the WPNUX on-boarding experience.
     *
     * @return bool
     */
    protected function isSellingOnlyDigitalProducts() : bool
    {
        if (! ManagedWooCommerceRepository::hasCompletedWPNuxOnboarding()) {
            return false;
        }

        $features = $this->getWPNuxOnboardingFeatures();

        if (! ArrayHelper::accessible($features)) {
            return false;
        }

        return ! ArrayHelper::contains($features, 'physical_goods');
    }

    /**
     * Gets the features selected data added by the WPNux template on-boarding system.
     *
     * @return array|null
     */
    protected function getWPNuxOnboardingFeatures()
    {
        return ArrayHelper::get(static::getWPNuxOnboardingData(), '_meta.features');
    }

    /**
     * Gets the export data added by the WPNux template on-boarding system.
     *
     * @return array
     */
    protected static function getWPNuxOnboardingData() : array
    {
        return ManagedWooCommerceRepository::hasCompletedWPNuxOnboarding() ? ArrayHelper::wrap(json_decode(get_option('wpnux_export_data'), true)) : [];
    }

    /**
     * Determines whether the installation is overriding any of the WooCommerce email templates.
     *
     * @return bool
     * @throws Exception
     */
    private function hasEmailTemplateOverrides() : bool
    {
        return ! empty(TemplatesRepository::getEmailTemplateOverrides());
    }
}
