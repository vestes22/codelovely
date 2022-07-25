<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Settings\Traits\CanUpdateSettingsUsingRequestDataTrait;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings\Settings;
use GoDaddy\WordPress\MWC\Core\Settings\API\Adapters\ControlOptionsAdapter;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class OnboardingSettingsController extends AbstractController implements ComponentContract
{
    use CanUpdateSettingsUsingRequestDataTrait;

    /** @var string */
    protected $route = 'settings/onboarding';

    /**
     * Initializes the controller.
     */
    public function load()
    {
        $this->registerRoutes();
    }

    /**
     * Registers the API routes for the endpoints provided by the controller.
     */
    public function registerRoutes()
    {
        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItems'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}", [
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'updateItems'],
                'permission_callback' => [$this, 'updateItemsPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Gets a list of onboarding settings.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getItems(WP_REST_Request $request)
    {
        $query = StringHelper::sanitize($request->get_param('query') ?? '');
        $filtersIds = ArrayHelper::wrap(ArrayHelper::get(
            json_decode($query, true),
            'filters.id',
            []
        ));

        $response = $this->prepareItems($this->getSettingsInstance());

        if ($filtersIds) {
            ArrayHelper::set($response, 'settings', $this->filterSettings($response['settings'], $filtersIds));
        }

        return rest_ensure_response($response);
    }

    /**
     * Updates a list of onboarding settings.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function updateItems(WP_REST_Request $request)
    {
        try {
            $requestSettings = ArrayHelper::wrap($request->get_param('settings'));

            $this->validateSettingsForUpdateItems($requestSettings);

            $settings = $this->getSettingsInstance();

            $this->updateSettingsValues($settings, $requestSettings);
        } catch (InvalidArgumentException $exception) {
            return $this->getRestResponseError('mwc_core_onboarding_update_settings_error', $exception->getMessage(), $exception->getCode());
        }

        return rest_ensure_response($this->prepareItems($settings));
    }

    /**
     * Validate settings param for a request to update items.
     *
     * @param array $settings
     * @throws InvalidArgumentException
     */
    protected function validateSettingsForUpdateItems(array $settings)
    {
        if (empty($settings)) {
            throw new InvalidArgumentException(__('A non-empty settings parameter must be provided.', 'mwc-core'), 400);
        }

        foreach ($settings as $setting) {
            if (! ArrayHelper::has($setting, ['name', 'value'])) {
                throw new InvalidArgumentException(__('Each setting object must contain name and value keys.', 'mwc-core'), 400);
            }
        }
    }

    /**
     * Filters settings by id from array.
     *
     * @param array $settings
     * @param array $filtersIds
     */
    protected function filterSettings(array $settings, array $filtersIds): array
    {
        return ArrayHelper::where(
            $settings,
            function (array $setting) use ($filtersIds) {
                return in_array($setting['id'], $filtersIds);
            },
            false
        );
    }

    /**
     * Gets an array of arrays with data representing the Onboarding settings.
     *
     * @param ConfigurableContract $settingGroup Onboarding settings
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
     * Gets an array with data representing the given Onboarding setting object.
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
                'options'     => $this->getControlOptionsAdapter($control->getOptions())->convertFromSource(),
                'placeholder' => $control->getPlaceholder(),
            ],
        ];
    }

    /**
     * @param array|null $options
     * @return ControlOptionsAdapter
     */
    protected function getControlOptionsAdapter($options) : ControlOptionsAdapter
    {
        return new ControlOptionsAdapter(ArrayHelper::wrap($options));
    }

    /**
     * Gets a WordPress error object to be served as a REST response error.
     *
     * @param string $errorCode
     * @param string $errorMessage
     * @param int $statusCode
     * @return WP_Error
     */
    protected function getRestResponseError(string $errorCode, string $errorMessage, int $statusCode) : WP_Error
    {
        return new WP_Error($errorCode, $errorMessage, [
            'status' => $statusCode,
        ]);
    }

    /**
     * Determines if the current user has permissions to issue requests to get items.
     *
     * @return bool
     */
    public function getItemsPermissionsCheck() : bool
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Determines if the current user has permissions to issue requests to update items.
     *
     * @return bool
     */
    public function updateItemsPermissionsCheck() : bool
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Gets the item schema.
     *
     * @return array
     */
    public function getItemSchema(): array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'setting',
            'type'       => 'object',
            'properties' => [
                'id'            => [
                    'description' => __('Unique onboarding setting ID.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'name'          => [
                    'description' => __('Unique onboarding setting name (matches the ID).', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'label'         => [
                    'description' => __('Onboarding setting label.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'description'   => [
                    'description' => __('Onboarding setting description.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'options'       => [
                    'description' => __('A list of available options for the onboarding setting values.', 'mwc-core'),
                    'type'        => 'array',
                    'items'       => [
                        'type' => 'string',
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'default'       => [
                    'description' => __('Onboarding setting default value.', 'mwc-core'),
                    'type'        => 'mixed',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'value'         => [
                    'description' => __('Onboarding setting value.', 'mwc-core'),
                    'type'        => 'mixed',
                    'context'     => ['view', 'edit'],
                    'readonly'    => false,
                ],
                'isMultivalued' => [
                    'description' => __('Whether the onboarding setting can have multiple values.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'isRequired'    => [
                    'description' => __('Whether the onboarding setting is required.', 'mwc-core'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'group'         => [
                    'description' => __('Onboarding setting group, if applicable.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'control'       => [
                    'description' => __('Onboarding setting control details.', 'mwc-core'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                    'properties'  => [
                        'type'    => [
                            'description' => __('Onboarding setting control type.', 'mwc-core'),
                            'type'        => 'string',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                        'options' => [
                            'description' => __('A list of available options for the onboarding setting control.', 'mwc-core'),
                            'type'        => 'array',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                            'items'       => [
                                'type' => 'string',
                            ],
                        ],
                        'placeholder' => [
                            'description' => __('Optional input placeholder for the onboarding setting control.', 'mwc-core'),
                            'type'        => 'mixed',
                            'context'     => ['view', 'edit'],
                            'readonly'    => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get an instance of Onboarding Settings class.
     *
     * @return Settings
     */
    protected function getSettingsInstance(): Settings
    {
        return new Settings();
    }
}
