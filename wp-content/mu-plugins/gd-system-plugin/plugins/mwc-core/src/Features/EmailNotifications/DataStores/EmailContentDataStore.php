<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailContentContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailContentNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\DefaultEmailContent;

/**
 * Data store for email content settings.
 *
 * @since x.y.z
 */
class EmailContentDataStore
{
    /** @var string the base option name to be used for reading the email content settings */
    private $settingsOptionNameBaseTemplate = 'mwc_%s_email_notification_content';

    /** @var array map of email content IDs to MJML files */
    private $structuredContentPaths = [
        'new_order'                         => 'admin-order.mjml',
        'cancelled_order'                   => 'admin-order.mjml',
        'failed_order'                      => 'admin-order.mjml',
        'customer_new_account'              => 'user.mjml',
        'customer_reset_password'           => 'user.mjml',
        'customer_note'                     => 'customer-order-note.mjml',
        'customer_completed_order'          => 'customer-order.mjml',
        'customer_item_shipped'             => 'customer-order.mjml',
        'customer_refunded_order'           => 'customer-order.mjml',
        'customer_on_hold_order'            => 'customer-order.mjml',
        'customer_processing_order'         => 'customer-order.mjml',
        'customer_partially_refunded_order' => 'customer-order.mjml',
        'customer_invoice'                  => 'customer-order.mjml',
    ];

    /**
     * Gets an email content with given ID and reads its settings.
     *
     * @since x.y.z
     *
     * @param string $id
     * @return EmailContentContract
     * @throws EmailContentNotFoundException
     */
    public function read(string $id) : EmailContentContract
    {
        $content = (new DefaultEmailContent())->setId($id);

        $this->setStructuredContentPath($content);

        $this->getOptionsSettingsDataStore($this->getSettingsOptionNameTemplate($id))
            ->read($content);

        return $content;
    }

    /**
     * Sets the structured content path for the given email content.
     *
     * This method expects to find an MJML content template file in the
     * templates/email-notifications/mjml/content directory.
     *
     * @param EmailContentContract $content the email content object
     * @throws EmailContentNotFoundException
     */
    protected function setStructuredContentPath(EmailContentContract $content)
    {
        $structuredContentPath = $this->getStructuredContentPath($content);

        if (! file_exists($structuredContentPath)) {
            throw new EmailContentNotFoundException(sprintf(
                __('No content template file found for the ID %s.', 'mwc-core'),
                $content->getId()
            ));
        }

        $content->setStructuredContentPath($structuredContentPath);
    }

    /**
     * Gets the structured content path for the given email content instance.
     *
     * @param EmailContentContract $content the email content object
     * @return string
     */
    protected function getStructuredContentPath(EmailContentContract $content) : string
    {
        if (! $filename = ArrayHelper::get($this->structuredContentPaths, $content->getId(), '')) {
            return '';
        }

        return $this->getTemplatesDirectory("email-notifications/mjml/content/{$filename}");
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
     * Saves the settings of a given email content object.
     *
     * @since x.y.z
     *
     * @param EmailContentContract $emailContent
     * @return EmailContentContract
     */
    public function save(EmailContentContract $emailContent) : EmailContentContract
    {
        $this->getOptionsSettingsDataStore($this->getSettingsOptionNameTemplate($emailContent->getId()))
            ->save($emailContent);

        return $emailContent;
    }

    /**
     * Deletes the settings of a given email content object.
     *
     * @since x.y.z
     *
     * @param EmailContentContract $emailContent
     * @return EmailContentContract
     */
    public function delete(EmailContentContract $emailContent) : EmailContentContract
    {
        $this->getOptionsSettingsDataStore($this->getSettingsOptionNameTemplate($emailContent->getId()))
            ->delete($emailContent);

        return $emailContent;
    }

    /**
     * Gets the options settings data store for the email content.
     *
     * @since x.y.z
     *
     * @param string $optionNameTemplate
     * @return OptionsSettingsDataStore
     */
    protected function getOptionsSettingsDataStore(string $optionNameTemplate) : OptionsSettingsDataStore
    {
        return new OptionsSettingsDataStore($optionNameTemplate);
    }

    /**
     * Gets the option name template to access an email content's settings.
     *
     * @since x.y.z
     *
     * @param string $emailContentId
     * @return string
     */
    private function getSettingsOptionNameTemplate(string $emailContentId) : string
    {
        return sprintf($this->settingsOptionNameBaseTemplate, $emailContentId).'_'.OptionsSettingsDataStore::SETTING_ID_MERGE_TAG;
    }
}
