<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailTemplateContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\DefaultEmailTemplate;

/**
 * Data store for email notifications template settings.
 *
 * @since 2.15.0
 */
class EmailTemplateDataStore
{
    /** @var string the base option name to be used for reading the email template settings */
    private $settingsOptionNameBaseTemplate = 'mwc_%s_email_notification_template';

    protected $templates = [
        'default' => DefaultEmailTemplate::class,
    ];

    /**
     * Gets an email template with given ID and reads its settings.
     *
     * @param string $id
     * @return EmailTemplateContract
     * @throws EmailTemplateNotFoundException|InvalidClassNameException
     */
    public function read(string $id) : EmailTemplateContract
    {
        /** @var DefaultEmailTemplate */
        $template = $this->getTemplateInstance($id);

        $this->setStructuredContentPath($template);

        $this->getOptionsSettingsDataStore($this->getSettingsOptionNameTemplate($id))
            ->read($template);

        return $template;
    }

    /**
     * Gets the email notification template instance from the given ID.
     *
     * @param string $id
     * @return EmailTemplateContract
     * @throws EmailTemplateNotFoundException|InvalidClassNameException
     */
    protected function getTemplateInstance(string $id) : EmailTemplateContract
    {
        if (! ArrayHelper::exists($this->templates, $id)) {
            throw new EmailTemplateNotFoundException(sprintf(
                __('No email notification template found with the ID %s.', 'mwc-core'),
                $id
            ));
        }

        $class = ArrayHelper::get($this->templates, $id);

        if (! is_a($class, EmailTemplateContract::class, true)) {
            throw new InvalidClassNameException(sprintf(
                __('The class for %s must implement the EmailTemplateContract interface', 'mwc-core'),
                $id
            ));
        }

        return (new $class())->setId($id);
    }

    /**
     * Sets the structured content path for the given template.
     *
     * This method expects to find an MJML template file in the templates/email-notifications/mjml/
     * directory. The name of the file must match the ID of the template.
     *
     * @param EmailTemplateContract $template the template object
     * @throws EmailTemplateNotFoundException
     */
    protected function setStructuredContentPath(EmailTemplateContract $template)
    {
        $structuredContentPath = $this->getTemplatesDirectory("email-notifications/mjml/{$template->getId()}.mjml");

        if (! file_exists($structuredContentPath)) {
            throw new EmailTemplateNotFoundException(sprintf(
                __('No template file found with the ID %s.', 'mwc-core'),
                $template->getId()
            ));
        }

        $template->setStructuredContentPath($structuredContentPath);
    }

    /**
     * Gets the path to the plugin's templates directory.
     *
     * TODO: add this method to the WordPressRepository class in mwc-common {wvega 2021-10-05}
     *
     * @param string $path optional path
     * @return string
     */
    protected function getTemplatesDirectory(string $path = '') : string
    {
        if (! $config = Configuration::get('mwc.directory')) {
            return '';
        }

        $pluginDirectory = StringHelper::trailingSlash($config);

        return "{$pluginDirectory}templates/{$path}";
    }

    /**
     * Saves the settings of a given email template object.
     *
     * @since 2.15.0
     *
     * @param EmailTemplateContract $emailTemplate
     * @return EmailTemplateContract
     */
    public function save(EmailTemplateContract $emailTemplate) : EmailTemplateContract
    {
        $this->getOptionsSettingsDataStore($this->getSettingsOptionNameTemplate($emailTemplate->getId()))
            ->save($emailTemplate);

        return $emailTemplate;
    }

    /**
     * Deletes the settings of a given email template object.
     *
     * @since 2.15.0
     *
     * @param EmailTemplateContract $emailTemplate
     * @return EmailTemplateContract
     */
    public function delete(EmailTemplateContract $emailTemplate) : EmailTemplateContract
    {
        $this->getOptionsSettingsDataStore($this->getSettingsOptionNameTemplate($emailTemplate->getId()))
            ->delete($emailTemplate);

        return $emailTemplate;
    }

    /**
     * Gets the options settings data store for the email template.
     *
     * @since 2.15.0
     *
     * @param string $optionNameTemplate
     * @return RecursiveOptionsSettingsDataStore
     */
    protected function getOptionsSettingsDataStore(string $optionNameTemplate) : RecursiveOptionsSettingsDataStore
    {
        return new RecursiveOptionsSettingsDataStore($optionNameTemplate);
    }

    /**
     * Gets the option name template to access an email template's settings.
     *
     * @since 2.15.0
     *
     * @param string $emailTemplateId
     * @return string
     */
    private function getSettingsOptionNameTemplate(string $emailTemplateId) : string
    {
        return sprintf($this->settingsOptionNameBaseTemplate, $emailTemplateId).'_'.OptionsSettingsDataStore::SETTING_ID_MERGE_TAG;
    }
}
