<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;
use GoDaddy\WordPress\MWC\Common\Contracts\NotificationContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;

/**
 * The Email Notification Contract.
 *
 * @since 2.15.0
 */
interface EmailNotificationContract extends HasLabelContract, NotificationContract, ConfigurableContract
{
    /**
     * Gets the email notification ID.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Gets the email notification description.
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Gets the email notification subject.
     *
     * @return string|null
     */
    public function getSubject();

    /**
     * Gets the email notification template.
     *
     * @return EmailTemplateContract|null
     */
    public function getTemplate();

    /**
     * Gets the email notification content.
     *
     * @return EmailContentContract|null
     */
    public function getContent();

    /**
     * Gets the content type for the content of this email notification.
     *
     * @return string
     */
    public function getContentType() : string;

    /**
     * Gets the email notification categories.
     *
     * @return string[]
     */
    public function getCategories() : array;

    /**
     * Gets the email notification structured body.
     *
     * @return string
     */
    public function getStructuredBody() : string;

    /**
     * Gets the email notification plain body.
     *
     * @return string
     */
    public function getPlainBody() : string;

    /**
     * Gets data from the registered data providers.
     *
     * @return array
     */
    public function getData() : array;

    /**
     * Gets preview data from the registered data providers.
     *
     * @return array
     */
    public function getPreviewData() : array;

    /**
     * Gets placeholders from the registered data providers.
     *
     * @return array
     */
    public function getPlaceholders() : array;

    /**
     * Gets data providers for the email notification.
     *
     * @return DataProviderContract[]
     */
    public function getDataProviders() : array;

    /**
     * Checks if email notification is enabled.
     *
     * @return bool
     */
    public function isEnabled() : bool;

    /**
     * Checks if email notification is manual.
     *
     * @return bool|null
     */
    public function isManual();

    /**
     * Checks whether the email notification can be edited.
     *
     * @return bool
     */
    public function isEditable() : bool;

    /**
     * Checks if the email notification will be sent to an administrator.
     *
     * @return bool|null
     */
    public function isSentToAdministrator();

    /**
     * Sets the email notification ID.
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value);

    /**
     * Sets the email notification description.
     *
     * @param string $value
     * @return self
     */
    public function setDescription(string $value);

    /**
     * Sets the email notification subject.
     *
     * @param string $value
     * @return self
     */
    public function setSubject(string $value);

    /**
     * Sets the email notification template.
     *
     * @param EmailTemplateContract $value
     * @return self
     */
    public function setTemplate(EmailTemplateContract $value);

    /**
     * Sets the email notification content object.
     *
     * @param EmailContentContract $value
     * @return self
     */
    public function setContent(EmailContentContract $value);

    /**
     * Sets the content type for the content of this email notification.
     *
     * @param string $value
     * @return self
     */
    public function setContentType(string $value);

    /**
     * Sets the email notification categories.
     *
     * @param array $value
     * @return self
     */
    public function setCategories(array $value);

    /**
     * Sets whether the email notification is manual.
     *
     * @param bool $value
     * @return self
     */
    public function setManual(bool $value);

    /**
     * Sets whether the email notification will be sent to an administrator.
     *
     * @param bool $value
     * @return self
     */
    public function setSentToAdministrator(bool $value);

    /**
     * Sets whether the email notification can be edited.
     *
     * @param bool $value
     * @return self
     */
    public function setEditable(bool $value);

    /**
     * Sets the data providers for the email notification.
     *
     * @param DataProviderContract[]
     * @return self
     */
    public function setDataProviders(array $value);

    /**
     * Adds a data provider for the email notification.
     *
     * @param DataProviderContract $dataProvider
     * @return self
     */
    public function addDataProvider(DataProviderContract $dataProvider);

    /**
     * Removes a data provider for the email notification.
     *
     * @param DataProviderContract $dataProvider
     * @return self
     */
    public function removeDataProvider(DataProviderContract $dataProvider);
}
