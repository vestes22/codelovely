<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Common\Settings\Traits\HasSettingsTrait;

/**
 * This class will load the structured content from a MJML template file.
 *
 * @since 2.15.0
 */
class DefaultEmailContent extends AbstractFileEmailContent
{
    use HasSettingsTrait {
        getConfiguration as traitGetConfiguration;
    }

    /** @var string */
    const SETTING_ID_HEADING = 'heading';

    /** @var string */
    const SETTING_ID_ADDITIONAL_CONTENT = 'additionalContent';

    /**
     * Gets the default email content configuration.
     *
     * This method "overrides" HasSettingsTrait:getConfiguration() to apply special formatting to any setting if necessary.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        $configuration = $this->traitGetConfiguration();

        if ($additionalContent = ArrayHelper::get($configuration, 'additionalContent')) {
            $configuration['additionalContent'] = wp_kses_post(wpautop(wptexturize($additionalContent)));
        }

        return $configuration;
    }

    /**
     * Gets the structured content.
     *
     * @since 2.15.0
     *
     * @return string
     */
    public function getStructuredContent() : string
    {
        return $this->getContentFromFile();
    }

    /**
     * Gets the initial settings.
     *
     * @since 2.15.0
     *
     * @return EmailNotificationSetting[]
     */
    public function getInitialSettings() : array
    {
        return [
            (new EmailNotificationSetting())
                ->setId(static::SETTING_ID_HEADING)
                ->setName(static::SETTING_ID_HEADING)
                ->setLabel(__('Heading', 'mwc-core'))
                ->setType(EmailNotificationSetting::TYPE_STRING)
                ->setControl((new Control())
                    ->setType(Control::TYPE_TEXT)
                ),
            (new EmailNotificationSetting())
                ->setId(static::SETTING_ID_ADDITIONAL_CONTENT)
                ->setName(static::SETTING_ID_ADDITIONAL_CONTENT)
                ->setLabel(__('Additional content', 'mwc-core'))
                ->setType(EmailNotificationSetting::TYPE_STRING)
                ->setControl((new Control())
                    ->setType(Control::TYPE_TEXTAREA)
                ),
        ];
    }
}
