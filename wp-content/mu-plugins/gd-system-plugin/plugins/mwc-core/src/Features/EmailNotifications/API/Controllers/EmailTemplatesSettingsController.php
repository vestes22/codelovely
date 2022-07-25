<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\API;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce\EmailTemplateDataStore;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class EmailTemplatesSettingsController extends AbstractController implements ComponentContract
{
    /** @var string */
    protected $route = 'settings/email-templates';

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
        register_rest_route($this->namespace, "/{$this->route}/(?P<emailTemplateId>[a-zA-Z0-9_-]+)", [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Returns the schema for REST items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'emailTemplateSetting',
            'type' => 'object',
            'properties' => [
                'name' => [
                    'description' => __('Unique email template setting name.', 'mwc-core'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'label' => [
                    'description' => __('Email template setting label.', 'mwc-core'),
                    'type' => 'string',
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'subgroups' => [
                    'description' => __('Email template settings subgroups.', 'mwc-core'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                    ],
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
                'settings' => [
                    'description' => __('Root settings for an email template.', 'mwc-core'),
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                    ],
                    'context' => ['view', 'edit'],
                    'readonly' => true,
                ],
            ],
        ];
    }

    /**
     * Gets an Email Template settings.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function getItem(WP_REST_Request $request)
    {
        try {
            $emailTemplate = $this->getEmailTemplateDataStore()->read(StringHelper::sanitize($request->get_param('emailTemplateId')));

            $response = $this->prepareItem($emailTemplate);
        } catch (BaseException $exception) {
            $response = new WP_Error($exception->getCode(), $exception->getMessage(), [
                'status' => $exception->getCode(),
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Determines if the current user has permissions to issue requests to get items.
     *
     * @return bool
     */
    public function getItemsPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Gets an array with data representing the given email template object.
     *
     * @param ConfigurableContract $emailTemplate email template object
     * @return array
     */
    protected function prepareItem(ConfigurableContract $emailTemplate) : array
    {
        return [
            'name' => $emailTemplate->getName(),
            'label' => $emailTemplate->getLabel(),
            'subgroups' => array_map(function (ConfigurableContract $configurable) {
                return $this->prepareItem($configurable);
            }, $emailTemplate->getSettingsSubgroups()),
            'settings' => array_map(function (SettingContract $setting) use ($emailTemplate) {
                return $this->prepareSetting($setting, $emailTemplate->getName());
            }, $emailTemplate->getSettings()),
        ];
    }

    /**
     * Gets an instance of the Email Template datastore.
     *
     * @return EmailTemplateDataStore
     */
    protected function getEmailTemplateDataStore() : EmailTemplateDataStore
    {
        return new EmailTemplateDataStore();
    }

    /**
     * Gets an array with data representing the given setting object.
     *
     * @param SettingContract $setting
     * @param string|null $group
     * @return array
     */
    protected function prepareSetting(SettingContract $setting, string $group = null) : array
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
                'type'    => $control->getType(),
                'options' => $control->getOptions(),
            ],
        ];
    }
}
