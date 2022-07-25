<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\Controllers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\TemplatesRepository;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Traits\CanUpdateSettingsUsingRequestDataTrait;
use GoDaddy\WordPress\MWC\Core\Email\RenderableEmail;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\API\API;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailTemplateContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce\EmailTemplateDataStore;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailNotifications;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\EmailPreviewBuilder;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\AbstractNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\HasEmailTemplateOverridesException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotification;
use GoDaddy\WordPress\MWC\Dashboard\API\Controllers\AbstractController;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Email templates API handler.
 */
class EmailTemplatesController extends AbstractController implements ComponentContract
{
    use CanUpdateSettingsUsingRequestDataTrait;

    /** @var string */
    protected $route = 'email-templates';

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
        $patternEmailTemplateId = '(?P<emailTemplateId>[a-zA-Z0-9_-]+)';

        register_rest_route($this->namespace, "/{$this->route}/{$patternEmailTemplateId}", [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'getItem'],
                'permission_callback' => [$this, 'getItemsPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/{$patternEmailTemplateId}", [
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'updateItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
        ]);

        register_rest_route($this->namespace, "/{$this->route}/(?P<emailTemplateId>[a-zA-Z0-9_-]+)/reset", [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'resetItem'],
                'permission_callback' => [$this, 'updateItemPermissionsCheck'],
            ],
        ]);
    }

    /**
     * Gets an email template.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function getItem(WP_REST_Request $request)
    {
        try {
            $emailTemplate = $this->getEmailTemplateDataStore()->read($this->getEmailTemplateId($request));
            $response = ['emailTemplate' => $this->prepareItem($emailTemplate)];
        } catch (EmailTemplateNotFoundException $exception) {
            return $this->getEmailTemplateNotFoundError($exception->getMessage(), $exception->getCode() ?: 404);
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
     * Gets the ID of the email template from the request.
     *
     * @param WP_REST_Request $request
     * @return string
     */
    protected function getEmailTemplateId(WP_REST_Request $request) : string
    {
        return StringHelper::sanitize($request->get_param('emailTemplateId'));
    }

    /**
     * Resets an email template's settings to defaults.
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function resetItem(WP_REST_Request $request)
    {
        try {
            $emailTemplate = $this->getEmailTemplateDataStore()->read(StringHelper::sanitize($request->get_param('emailTemplateId')));

            foreach ($emailTemplate->getSettingsSubgroups() as $settingsSubgroup) {
                $this->resetSettings($settingsSubgroup, $settingsSubgroup->getSettingsId(), []);
            }

            $this->getEmailTemplateDataStore()->save($emailTemplate);

            // return empty response if successful
            $response = null;
        } catch (EmailTemplateNotFoundException $exception) {
            $response = new WP_Error('mwc_core_email_templates_update_settings_error', $exception->getMessage(), [
                'status' => $exception->getCode() ?: 404,
            ]);
        } catch (InvalidArgumentException $exception) {
            $response = new WP_Error('mwc_core_email_templates_update_settings_error', $exception->getMessage(), [
                'status' => $exception->getCode() ?: 400,
            ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Recursively resets settings to to their defaults.
     *
     * TODO: remove the handling of presets from the controller and the DefaultEmailTemplate class {wvega 2021-10-17}
     *
     * Initially we thought we needed to reset the template settings to a set of values that
     * were different from the configured defaults. For example, the WooCommerce email template
     * defaults. Unfortunately, that was a misunderstanding of the acceptance criteria and we should
     * always reset setting values to the configured defaults.
     *
     * @param ConfigurableContract $settingGroup
     * @param string $parentPath
     * @param array $preset
     */
    protected function resetSettings(ConfigurableContract $settingGroup, string $parentPath, array $preset)
    {
        foreach ($settingGroup->getSettingsSubgroups() as $subGroup) {
            $this->resetSettings($subGroup, "{$parentPath}.{$subGroup->getSettingsId()}", $preset);
        }

        foreach ($settingGroup->getSettings() as $setting) {
            $settingGroup->updateSettingValue($setting->getId(), $setting->getDefault());
        }
    }

    /**
     * Gets an instance of the WooCommerce email template data store.
     *
     * @return EmailTemplateDataStore
     */
    protected function getEmailTemplateDataStore() : EmailTemplateDataStore
    {
        return new EmailTemplateDataStore();
    }

    /**
     * Gets an email preview for a given email notification.

     * @param EmailNotificationContract $emailNotification
     * @return RenderableEmail
     */
    protected function getEmailPreview(EmailNotificationContract $emailNotification) : RenderableEmail
    {
        return (new EmailPreviewBuilder($emailNotification))->build();
    }

    /**
     * Gets an email notification instance for a given template.
     *
     * @param EmailTemplateContract $emailTemplate
     * @return EmailNotification
     */
    protected function getEmailNotificationForPreview(EmailTemplateContract $emailTemplate) : EmailNotification
    {
        return (new EmailNotification())->setTemplate($emailTemplate);
    }

    /**
     * Gets an array with data representing the given email template object.
     *
     * @param EmailTemplateContract $emailTemplate email notification object
     * @return array
     * @throws Exception
     */
    protected function prepareItem(EmailTemplateContract $emailTemplate) : array
    {
        $emailNotification = $this->getEmailNotificationForPreview($emailTemplate);
        $preview = $this->getEmailPreview($emailNotification);

        return [
            'id'           => $emailTemplate->getId(),
            'name'         => $emailTemplate->getName(),
            'label'        => $emailTemplate->getLabel(),
            'placeholders' => $emailNotification->getPlaceholders(),
            'preview'      => [
                $preview->getBodyFormat() => $preview->getBody(),
                'variables'               => $preview->getVariables(),
            ],
        ];
    }

    /**
     * Updates the settings for an email template.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     * @throws Exception
     */
    public function updateItem(WP_REST_Request $request)
    {
        try {
            if (! EmailNotifications::isEnabled() && ! empty(TemplatesRepository::getEmailTemplateOverrides())) {
                throw new HasEmailTemplateOverridesException(__("The site has email template overrides and hasn't enabled the Email Notifications feature.", 'mwc-core'));
            }

            $settings = ArrayHelper::wrap($request->get_param('settings'));
            $subgroups = array_filter(ArrayHelper::wrap($request->get_param('subgroups')));

            if (empty($settings) && empty($subgroups)) {
                throw new InvalidArgumentException(__('A non-empty settings or subgroups parameter must be provided.', 'mwc-core'), 400);
            }

            $emailTemplate = $this->getEmailTemplateDataStore()->read($this->getEmailTemplateId($request));

            $this->updateEmailTemplateSettings($emailTemplate, $settings, $subgroups);

            $response = ['emailTemplate' => $this->prepareItem($emailTemplate)];
        } catch (HasEmailTemplateOverridesException $exception) {
            return $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode());
        } catch (AbstractNotFoundException $exception) {
            return $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode());
        } catch (InvalidArgumentException $exception) {
            return $this->getSettingsUpdateError($exception->getMessage(), $exception->getCode() ?: 400);
        }

        return rest_ensure_response($response);
    }

    /**
     * Determines if the current user has permissions to issue update requests.
     *
     * @return bool
     */
    public function updateItemPermissionsCheck() : bool
    {
        return API::hasAPIAccess();
    }

    /**
     * Updates the given email template's settings and settings subgroups with the given values.
     *
     * @param EmailTemplateContract $emailTemplate
     * @param array $settings data for the settings to update
     * @param array $subgroups data for the settings subgroups to update
     * @return EmailTemplateContract
     * @throws InvalidArgumentException
     */
    protected function updateEmailTemplateSettings(EmailTemplateContract $emailTemplate, array $settings, array $subgroups) : EmailTemplateContract
    {
        $this->updateConfigurableSettings($emailTemplate, $settings, $subgroups);

        return $this->getEmailTemplateDataStore()->save($emailTemplate);
    }

    /**
     * Gets a WordPress error object to return for a failed request to update settings.
     *
     * @param string $message
     * @param int $statusCode
     * @return WP_Error
     */
    protected function getSettingsUpdateError(string $message, int $statusCode) : WP_Error
    {
        return $this->getRestResponseError('mwc_core_email_templates_update_settings_error', $message, $statusCode);
    }

    /**
     * Gets a WordPress error object to return for a failed request to get templates.
     *
     * @param string $message
     * @param int $statusCode
     * @return WP_Error
     */
    protected function getEmailTemplateNotFoundError(string $message, int $statusCode) : WP_Error
    {
        return $this->getRestResponseError('mwc_core_get_email_template_not_found_error', $message, $statusCode);
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
     * Gets the schema for REST items provided by the controller.
     *
     * @return array
     */
    public function getItemSchema() : array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'emailTemplate',
            'type'       => 'object',
            'properties' => [
                'id'           => [
                    'description' => __('Unique email template ID.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'name'         => [
                    'description' => __('Unique email template name (matches the ID).', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'label'        => [
                    'description' => __('Email template label.', 'mwc-core'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'placeholders' => [
                    'description' => __('List of email content placeholders.', 'mwc-core'),
                    'type'        => 'array',
                    'items'       => [
                        'type' => 'string',
                    ],
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
                'preview'      => [
                    'description' => __('Email template preview.', 'mwc-core'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ],
        ];
    }
}
