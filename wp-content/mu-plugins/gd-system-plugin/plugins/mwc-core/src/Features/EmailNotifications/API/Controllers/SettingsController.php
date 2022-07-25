<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\API;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\AbstractNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\SettingNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetEmailNotificationDataStoreTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetWooCommerceSettingsDataStoreTrait;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use InvalidArgumentException;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API controller for email notifications settings.
 */
class SettingsController extends AbstractController implements ComponentContract
{
    use CanGetEmailNotificationDataStoreTrait;
    use CanGetWooCommerceSettingsDataStoreTrait;

    /** @var string */
    protected $route = 'settings/email-notifications';

    /**
     * Initializes the controller.
     */
    public function load()
    {
        $this->registerRoutes();
    }

    /**
     * Registers the API routes.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, '/'.$this->route, [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->route.'/(?P<emailNotificationId>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getNotificationItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, '/'.$this->route.'/(?P<emailNotificationId>[a-zA-Z0-9_-]+)/(?P<settingId>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getSettingItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Gets all email notification settings.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItems(WP_REST_Request $request)
    {
        try {
            $settings = $this->getWooCommerceSettingsDataStore()->read(GeneralSettings::GROUP_ID);
            $response = $this->prepareItems($settings);
        } catch (AbstractNotFoundException $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets email notification's settings.
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     * @throws Exception
     */
    public function getNotificationItem(WP_REST_Request $request)
    {
        try {
            $emailNotificationId = StringHelper::sanitize($request->get_param('emailNotificationId'));
            $emailNotification = $this->getEmailNotificationDataStore()->read($emailNotificationId);

            $response = $this->prepareNotificationItem($emailNotification);
        } catch (BaseException $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets an email notification setting.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getSettingItem(WP_REST_Request $request)
    {
        try {
            $emailNotificationId = StringHelper::sanitize($request->get_param('emailNotificationId'));
            $emailNotification = $this->getEmailNotificationDataStore()->read($emailNotificationId);
            $settingId = StringHelper::sanitize($request->get_param('settingId'));

            // try first getting a setting for the email notification content, then from the email notification object itself
            try {
                $group = 'content';
                $setting = $this->getEmailNotificationContentSetting($emailNotification, $settingId)
                    ?? $this->getEmailNotificationSetting($emailNotification, $settingId);
            } catch (InvalidArgumentException $exception) {
                try {
                    $group = null;
                    $setting = $this->getEmailNotificationSetting($emailNotification, $settingId);
                } catch (InvalidArgumentException $exception) {
                    throw new SettingNotFoundException($exception->getMessage());
                }
            }

            $response = [
                'setting' => $this->prepareItem($setting, $group),
            ];
        } catch (BaseException $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Gets an email notification setting by ID.
     *
     * @param EmailNotificationContract $emailNotification
     * @param string $settingId
     * @return ConfigurableContract
     */
    protected function getEmailNotificationSetting(EmailNotificationContract $emailNotification, string $settingId)
    {
        return $emailNotification->getSetting($settingId);
    }

    /**
     * Gets an email notification content setting by ID.
     *
     * @param EmailNotificationContract $emailNotification
     * @param string $settingId
     * @return ConfigurableContract|null
     * @throws InvalidArgumentException
     */
    protected function getEmailNotificationContentSetting(EmailNotificationContract $emailNotification, string $settingId)
    {
        $content = $emailNotification->getContent();

        return $content ? $content->getSetting($settingId) : null;
    }

    /**
     * Gets an array with data representing the given email notification object.
     *
     * @param EmailNotificationContract $emailNotification
     * @return array
     * @throws Exception
     */
    protected function prepareNotificationItem(EmailNotificationContract $emailNotification) : array
    {
        return [
            'id'        => $emailNotification->getId(),
            'name'      => $emailNotification->getName(),
            'label'     => $emailNotification->getLabel(),
            'subgroups' => ['content'],
            'settings'  => ArrayHelper::combine(
                array_map(function (SettingContract $setting) {
                    return $this->prepareItem($setting);
                }, $emailNotification->getSettings()),
                array_map(function (SettingContract $setting) {
                    return $this->prepareItem($setting, 'content');
                }, $emailNotification->getContent()->getSettings())
            ),
        ];
    }

    /**
     * Gets an array of arrays with data representing the Email Notification settings.
     *
     * @param ConfigurableContract $settingGroup email notification objects
     * @return array
     */
    protected function prepareItems(ConfigurableContract $settingGroup) : array
    {
        return [
            'id'        => $settingGroup->getId(),
            'name'      => $settingGroup->getName(),
            'label'     => $settingGroup->getLabel(),
            'subgroups' => array_values(array_filter(array_map(static function (ConfigurableContract $settingGroup) {
                return $settingGroup->getSettingsId() ?: null;
            }, $settingGroup->getSettingsSubgroups()))),
            'settings'  => array_map(function (SettingContract $setting) {
                return $this->prepareItem($setting);
            }, $settingGroup->getSettings()),
        ];
    }

    /**
     * Gets an array with data representing the given email notification setting object.
     *
     * @param SettingContract $setting
     * @param string|null $group
     * @return array
     */
    protected function prepareItem(SettingContract $setting, $group = null) : array
    {
        $control = $setting->getControl();

        return [
            'id'            => $setting->getId(),
            'name'          => $setting->getName(),
            'label'         => $setting->getLabel(),
            'description'   => $setting->getDescription(),
            'options'       => $setting->getOptions(),
            'default'       => $setting->getDefault(),
            'value'         => $setting->getValue(),
            'isMultivalued' => $setting->isMultivalued(),
            'isRequired'    => $setting->isRequired(),
            'group'         => $group,
            'control'       => [
                'type'        => $control->getType(),
                'options'     => $control->getOptions(),
                'placeholder' => $control->getPlaceholder(),
            ],
        ];
    }

    /**
     * Checks the user permissions to get items.
     *
     * @return bool
     */
    public function getItemsPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
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
            'title'      => 'setting',
            'type'       => 'object',
            'properties' => [
                'id'            => [
                    'description' => __('Unique email notification setting ID.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'name'          => [
                    'description' => __('Unique email notification setting name (matches the ID).', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'label'         => [
                    'description' => __('Email notification setting label.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'description'   => [
                    'description' => __('Email notification setting description.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'options'       => [
                    'description' => __('A list of available options for the email notification setting values.', 'mwc-core'),
                    'type'        => 'array',
                    'items'       => [
                        'type' => 'string',
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'default'       => [
                    'description' => __('Email notification setting default value.', 'mwc-core'),
                    'type'        => 'mixed',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'value'         => [
                    'description' => __('Email notification setting value.', 'mwc-core'),
                    'type'        => 'mixed',
                    'context'     => ['view', 'edit'],
                    'readonly'    => false,
                ],
                'isMultivalued' => [
                    'description' => __('Whether the email notification setting can have multiple values.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isRequired'    => [
                    'description' => __('Whether the email notification setting is required.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'group'         => [
                    'description' => __('Email notifications setting group, if applicable.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'control'       => [
                    'description' => __('Email notifications setting control details.', 'mwc-core'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'properties'  => [
                        'type'    => [
                            'description' => __('Email notifications setting control type.', 'mwc-core'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'options' => [
                            'description' => __('A list of available options for the email notification setting control.', 'mwc-core'),
                            'type'        => 'array',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                            'items'       => [
                                'type' => 'string',
                            ],
                        ],
                        'placeholder' => [
                            'description' => __('Optional input placeholder for the email notification setting control.', 'mwc-core'),
                            'type'        => 'mixed',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
