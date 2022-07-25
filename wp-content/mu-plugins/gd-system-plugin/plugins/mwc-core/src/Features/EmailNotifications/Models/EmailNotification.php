<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\DateTimeRepository;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Common\Settings\Traits\HasSettingsTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailContentContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailTemplateContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\SiteDataProvider;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use InvalidArgumentException;

/**
 * Model for email notifications.
 *
 * @since 2.15.0
 */
class EmailNotification extends AbstractModel implements EmailNotificationContract
{
    use HasLabelTrait;
    use HasSettingsTrait {
        getConfiguration as traitGetConfiguration;
    }

    /** @var string */
    const SETTING_ID_ENABLED = 'enabled';

    /** @var string */
    const SETTING_ID_PREVIEW_TEXT = 'preview';

    /** @var string */
    const SETTING_ID_SUBJECT = 'subject';

    /** @var string */
    protected $id;

    /** @var string */
    protected $description = '';

    /** @var EmailTemplateContract */
    protected $template;

    /** @var EmailContentContract */
    protected $content;

    /** @var string */
    protected $contentType = 'text/mjml';

    /** @var string[] */
    protected $categories = [];

    /** @var bool */
    protected $manual = false;

    /** @var bool */
    protected $sentToAdministrator = false;

    /** @var bool */
    protected $editable = true;

    /** @var DataProviderContract[] */
    protected $dataProviders;

    /**
     * Gets the email notification ID.
     *
     * @since 2.15.0
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the email notification description.
     *
     * @since 2.15.0
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Gets the email notification subject.
     *
     * @since 2.15.0
     *
     * @return string|null
     */
    public function getSubject()
    {
        return $this->getSettingValue(static::SETTING_ID_SUBJECT);
    }

    /**
     * Gets the email notification template.
     *
     * @since 2.15.0
     *
     * @return EmailTemplateContract|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Gets the email notification content.
     *
     * @since 2.15.0
     *
     * @return EmailContentContract|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Gets the content type for the content of this email notification.
     *
     * @return string
     */
    public function getContentType() : string
    {
        return $this->contentType;
    }

    /**
     * Gets the email notification categories.
     *
     * @since 2.15.0
     *
     * @return string[]
     */
    public function getCategories() : array
    {
        return $this->categories;
    }

    /**
     * Gets the email notification structured body.
     *
     * @return string
     */
    public function getStructuredBody() : string
    {
        $template = $this->getTemplate();

        return $template ? $template->getStructuredContent() : '';
    }

    /**
     * Gets the email notification plaintext body.
     *
     * @since 2.15.0
     *
     * @return string
     */
    public function getPlainBody() : string
    {
        return '';
    }

    /**
     * Gets the email notification initial settings.
     *
     * @return SettingContract[]
     * @throws InvalidArgumentException|Exception
     */
    protected function getInitialSettings() : array
    {
        return [
            $this->getEnabledSettingObject(),
            $this->getSubjectSettingObject(),
            $this->getPreviewTextSettingObject(),
        ];
    }

    /**
     * Gets a setting object for the enabled setting.
     *
     * @return EmailNotificationSetting
     */
    protected function getEnabledSettingObject() : EmailNotificationSetting
    {
        return (new EmailNotificationSetting())
            ->setId(static::SETTING_ID_ENABLED)
            ->setName(static::SETTING_ID_ENABLED)
            ->setLabel(__('Enabled', 'mwc-core'))
            ->setType(EmailNotificationSetting::TYPE_BOOLEAN)
            ->setDefault(true)
            ->setControl((new Control())
                ->setType(Control::TYPE_CHECKBOX)
            );
    }

    /**
     * Gets a setting object for the preview text setting.
     *
     * @return EmailNotificationSetting
     * @throws Exception
     */
    protected function getPreviewTextSettingObject() : EmailNotificationSetting
    {
        return (new EmailNotificationSetting())
            ->setId(static::SETTING_ID_PREVIEW_TEXT)
            ->setName(static::SETTING_ID_PREVIEW_TEXT)
            ->setLabel(__('Preview text', 'mwc-core'))
            ->setType(EmailNotificationSetting::TYPE_STRING)
            ->setDefault('')
            ->setDescription(sprintf(
            /* translators: %1$s - opening a HTML tag, %2$s - closing a HTML tag */
                __('Preview text is shown after the subject line in some email inboxes. Maximum: 150 characters. %1$sLearn more%2$s', 'mwc-core'),
                '<a href="'.esc_url($this->getPreviewTextSettingHelpUrl()).'" target="_blank">', '</a>'
            ))
            ->setControl((new Control())
                ->setType(Control::TYPE_TEXTAREA)
                ->setPlaceholder(__('Enter preview text', 'mwc-core'))
            );
    }

    /**
     * Gets the URL used in the preview text setting description.
     *
     * @return string
     * @throws Exception
     */
    protected function getPreviewTextSettingHelpUrl() : string
    {
        return ManagedWooCommerceRepository::isReseller() ? 'https://www.secureserver.net/help/40889?pl_id='.ManagedWooCommerceRepository::getResellerId() : 'https://help.godaddy.com/help/40889';
    }

    /**
     * Gets a setting object for the subject setting.
     *
     * @return EmailNotificationSetting
     */
    protected function getSubjectSettingObject() : EmailNotificationSetting
    {
        return (new EmailNotificationSetting())
            ->setId(static::SETTING_ID_SUBJECT)
            ->setName(static::SETTING_ID_SUBJECT)
            ->setLabel(__('Subject', 'mwc-core'))
            ->setType(EmailNotificationSetting::TYPE_STRING)
            ->setDefault('')
            ->setControl((new Control())
                ->setType(Control::TYPE_TEXT)
            );
    }

    /**
     * Gets data for this email notification.
     *
     * Returns the from the registered data providers and the data generated
     * by the email notification itself.
     *
     * @return array
     * @throws Exception
     */
    public function getData() : array
    {
        $data = array_map(static function ($dataProvider) {
            return $dataProvider->getData();
        }, $this->getDataProviders());

        $data[] = $this->getAdditionalData();

        return ArrayHelper::combineRecursive(...$data);
    }

    /**
     * Gets preview data from the registered data providers.
     *
     * @return array
     * @throws Exception
     */
    public function getPreviewData() : array
    {
        $data = array_map(static function ($dataProvider) {
            if (is_callable([$dataProvider, 'getPreviewData'])) {
                return $dataProvider->getPreviewData();
            }

            return $dataProvider->getData();
        }, array_values($this->getDataProviders()));

        if (is_callable([$this, 'getAdditionalPreviewData'])) {
            $data[] = $this->getAdditionalPreviewData();
        }

        return ArrayHelper::combineRecursive(...$data);
    }

    /**
     * Gets additional data for this email notification.
     *
     * The data returned by this method will be merged with the data returned by
     * the registered data providers.
     *
     * @return array
     */
    protected function getAdditionalData() : array
    {
        return [];
    }

    /**
     * Gets placeholders from the registered data providers.
     *
     * @since 2.15.0
     *
     * @return array
     * @throws Exception
     */
    public function getPlaceholders() : array
    {
        $placeholders = [];

        foreach ($this->getDataProviders() as $dataProvider) {
            $placeholders = ArrayHelper::combine($placeholders, $dataProvider->getPlaceholders());
        }

        return $placeholders;
    }

    /**
     * Gets the initial email notification data providers.
     *
     * @return DataProviderContract[] by default this includes an instance of {@see SiteDataProvider}
     */
    protected function getInitialDataProviders() : array
    {
        return [
            new SiteDataProvider(),
        ];
    }

    /**
     * Gets the email notification data providers.
     *
     * @return DataProviderContract[]
     */
    public function getDataProviders() : array
    {
        if (null === $this->dataProviders) {
            $this->dataProviders = $this->getInitialDataProviders();
        }

        return $this->dataProviders;
    }

    /**
     * Determines whether the email notification is enabled.
     *
     * @since 2.15.0
     *
     * @return bool
     */
    public function isEnabled() : bool
    {
        return (bool) $this->getSettingValue(static::SETTING_ID_ENABLED);
    }

    /**
     * Determines whether the email notification is manually handled.
     *
     * @since 2.15.0
     *
     * @return bool|null
     */
    public function isManual()
    {
        return $this->manual;
    }

    /**
     * Determines whether the email notification should be sent to an administrator.
     *
     * @since 2.15.0
     *
     * @return bool|null
     */
    public function isSentToAdministrator()
    {
        return $this->sentToAdministrator;
    }

    /**
     * {@inheritdoc}
     */
    public function isEditable() : bool
    {
        return $this->editable;
    }

    /**
     * Sets the email notification ID.
     *
     * @since 2.15.0
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value) : EmailNotification
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the email notification description.
     *
     * @since 2.15.0
     *
     * @param string $value
     * @return self
     */
    public function setDescription(string $value) : EmailNotification
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Sets the email notification enabled status.
     *
     * @since 2.15.0
     *
     * @param bool $value
     * @return self
     */
    public function setEnabled(bool $value) : EmailNotification
    {
        $this->updateSettingValue(self::SETTING_ID_ENABLED, $value);

        return $this;
    }

    /**
     * Sets the email notification subject.
     *
     * @since 2.15.0
     *
     * @param string $value
     * @return self
     */
    public function setSubject(string $value) : EmailNotification
    {
        try {
            $this->updateSettingValue(self::SETTING_ID_SUBJECT, $value);
        } catch (InvalidArgumentException $exception) {
            // this exception shouldn't occur because any string should be accepted as valid subject
        }

        return $this;
    }

    /**
     * Sets the email notification template.
     *
     * @since 2.15.0
     *
     * @param EmailTemplateContract $value
     * @return self
     */
    public function setTemplate(EmailTemplateContract $value) : EmailNotification
    {
        $this->template = $value;

        return $this;
    }

    /**
     * Sets the email notification content.
     *
     * If a template is set when calling this method, it will also set the content for the template's inner content.
     *
     * @param EmailContentContract $value
     * @return self
     */
    public function setContent(EmailContentContract $value) : EmailNotification
    {
        $this->content = $value;

        if ($template = $this->getTemplate()) {
            $template->setInnerContentTemplate($this->getContent());
        }

        return $this;
    }

    /**
     * Sets the content type for the content of this email notification.
     *
     * @param string $value
     * @return self
     */
    public function setContentType(string $value) : EmailNotification
    {
        $this->contentType = $value;

        return $this;
    }

    /**
     * Sets the email notification categories;.
     *
     * @since 2.15.0
     *
     * @param array $value
     * @return self
     */
    public function setCategories(array $value) : EmailNotification
    {
        $this->categories = $value;

        return $this;
    }

    /**
     * Sets whether the email notification should be handled manually.
     *
     * @since 2.15.0
     *
     * @param bool $value
     * @return self
     */
    public function setManual(bool $value) : EmailNotification
    {
        $this->manual = $value;

        return $this;
    }

    /**
     * Sets whether the email notification should be sent to an administrator.
     *
     * @since 2.15.0
     *
     * @param bool $value
     * @return self
     */
    public function setSentToAdministrator(bool $value) : EmailNotification
    {
        $this->sentToAdministrator = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEditable(bool $value) : EmailNotification
    {
        $this->editable = $value;

        return $this;
    }

    /**
     * Sets the email notification data providers.
     *
     * @since 2.15.0
     *
     * @param DataProviderContract[] $value
     * @return self
     */
    public function setDataProviders(array $value) : EmailNotification
    {
        $this->dataProviders = [];

        foreach ($value as $dataProvider) {
            $this->dataProviders[get_class($dataProvider)] = $dataProvider;
        }

        return $this;
    }

    /**
     * Adds a data provider to the email notification's list of providers.
     *
     * @since 2.15.0
     *
     * @param DataProviderContract $dataProvider
     * @return self
     */
    public function addDataProvider(DataProviderContract $dataProvider) : EmailNotification
    {
        $this->dataProviders[get_class($dataProvider)] = $dataProvider;

        return $this;
    }

    /**
     * Removes a data provider from the email notification's list of providers.
     *
     * @since 2.15.0
     *
     * @param DataProviderContract $dataProvider
     * @return self
     */
    public function removeDataProvider(DataProviderContract $dataProvider) : EmailNotification
    {
        ArrayHelper::remove($this->dataProviders, get_class($dataProvider));

        return $this;
    }

    /**
     * Formats the given order's created at datetime to system's formatting.
     *
     * @param Order $order
     * @return string
     * @throws Exception
     */
    protected function getOrderCreatedAtFormatted(Order $order) : string
    {
        if ($createdAt = $order->getCreatedAt()) {
            return $createdAt->format(DateTimeRepository::getDateFormat());
        }

        return '';
    }

    /**
     * Gets the site title from the corresponding data provider.
     *
     * @return string
     */
    protected function getSiteTitle() : string
    {
        return ArrayHelper::get((new SiteDataProvider())->getData(), 'site_title');
    }

    /**
     * Gets the email notification configuration merged with the template and content configurations.
     *
     * @return array
     * @throws Exception
     */
    public function getConfiguration() : array
    {
        return ArrayHelper::combine(
            $this->traitGetConfiguration(),
            $this->getTemplate() ? $this->getTemplate()->getConfiguration() : [],
            $this->getContent() ? $this->getContent()->getConfiguration() : []
        );
    }
}
