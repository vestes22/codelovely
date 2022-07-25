<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Common\Settings\Models\SettingGroup;
use GoDaddy\WordPress\MWC\Common\Settings\Traits\HasSettingsTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailContentContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailTemplateContract;

/**
 * This class will load the structured content from a default MJML template file.
 *
 * @since 2.15.0
 */
class DefaultEmailTemplate extends AbstractFileEmailContent implements EmailTemplateContract
{
    use HasSettingsTrait {
        getConfiguration as traitGetConfiguration;
    }

    /** @var string */
    const SETTING_ID_HEADER_IMAGE = 'image';

    /** @var EmailContentContract */
    protected $innerContentTemplate;

    /** @var string identifier */
    protected $id = 'default';

    /** @var string name */
    protected $name = 'default';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setLabel(_x('Default', 'name of the default email notification template', 'mwc-core'));
    }

    /**
     * Gets the default email template configuration.
     *
     * This method "overrides" HasSettingsTrait:getConfiguration() to apply special formatting to any setting if necessary.
     *
     * @return array
     */
    public function getConfiguration() : array
    {
        $configuration = $this->traitGetConfiguration();

        if ($footerText = ArrayHelper::get($configuration, 'footer.footerText')) {
            $configuration['footer']['footerText'] = wp_kses_post(wpautop(wptexturize($footerText)));
        }

        return $configuration;
    }

    /**
     * Gets the structured content.
     *
     * TODO: test that the content placeholder is preserved when no inner content is set {wvega 2021-10-13}
     *
     * @return string
     */
    public function getStructuredContent() : string
    {
        $content = $this->getContentFromFile();

        if ($innerContentTemplate = $this->getInnerContentTemplate()) {
            return preg_replace('/\{{1,3}\s*content\s*\}{1,3}/', $innerContentTemplate->getStructuredContent(), $content);
        }

        return $content;
    }

    /**
     * Sets the inner content template.
     *
     * @since 2.15.0
     *
     * @param EmailContentContract $content
     * @return self
     */
    public function setInnerContentTemplate(EmailContentContract $content) : EmailTemplateContract
    {
        $this->innerContentTemplate = $content;

        return $this;
    }

    /**
     * Gets the inner content template.
     *
     * @since 2.15.0
     *
     * @return EmailContentContract|null
     */
    public function getInnerContentTemplate()
    {
        return $this->innerContentTemplate;
    }

    /**
     * Gets the initial settings subgroups.
     *
     * @return ConfigurableContract[]
     */
    protected function getInitialSettingsSubgroups() : array
    {
        $containerGroup = (new SettingGroup())
            ->setId('container')
            ->setName('container')
            ->setLabel(__('Container', 'mwc-core'))
            ->setSettings([
                $this->getColorSetting('#F5F5F5', 'backgroundColor', __('Background color', 'mwc-core')),
                (new EmailNotificationSetting())
                    ->setId('width')
                    ->setName('width')
                    ->setLabel(__('Email width', 'mwc-core'))
                    ->setType(EmailNotificationSetting::TYPE_STRING)
                    ->setOptions(['default', 'full'])
                    ->setDefault('default')
                    ->setControl(
                        (new Control())
                            ->setType(Control::TYPE_RADIO)
                            // set options in the Control to make their labels translatable
                            ->setOptions([
                                'default' => __('Default', 'mwc-core'),
                                'full'    => __('Full', 'mwc-core'),
                            ])
                    ),
            ]);

        $headerGroup = (new SettingGroup())
            ->setId('header')
            ->setName('header')
            ->setLabel(__('Header', 'mwc-core'))
            ->setSettings([
                (new EmailNotificationSetting())
                    ->setId('image')
                    ->setName('image')
                    ->setLabel(__('Header image', 'mwc-core'))
                    ->setType(EmailNotificationSetting::TYPE_ARRAY)
                    ->setControl((new Control())->setType(Control::TYPE_IMAGE_UPLOAD)),
                $this->getColorSetting('#D4DBE0', 'backgroundColor', __('Background color', 'mwc-core')),
            ])
            ->setSettingsSubgroups([
                (new SettingGroup())
                    ->setId('text')
                    ->setName('text')
                    ->setLabel(__('Text', 'mwc-core'))
                    ->setSettings([
                        $this->getColorSetting('#000000'),
                        $this->getFontFamilySetting(),
                        $this->getFontSizeSetting(24),
                    ]),
            ]);

        $bodyGroup = (new SettingGroup())
            ->setId('body')
            ->setName('body')
            ->setLabel(__('Body', 'mwc-core'))
            ->setSettings([
                $this->getColorSetting('#FFFFFF', 'backgroundColor', __('Body background color', 'mwc-core')),
            ])
            ->setSettingsSubgroups([
                (new SettingGroup())
                    ->setId('text')
                    ->setName('text')
                    ->setLabel(__('Text', 'mwc-core'))
                    ->setSettings([
                        $this->getColorSetting('#111111'),
                        $this->getColorSetting('#145FA9', 'linkColor', __('Link color', 'mwc-core')),
                        $this->getFontFamilySetting(),
                        $this->getFontSizeSetting(16),
                    ]),
                (new SettingGroup())
                    ->setId('heading')
                    ->setName('heading')
                    ->setLabel(__('Headings', 'mwc-core'))
                    ->setSettings([
                        $this->getFontFamilySetting(),
                        $this->getFontSizeSetting(24, 'h1FontSize', __('H1 font size', 'mwc-core')),
                        $this->getFontSizeSetting(20, 'h2FontSize', __('H2 font size', 'mwc-core')),
                        $this->getFontSizeSetting(18, 'h3FontSize', __('H3 font size', 'mwc-core')),
                    ]),
                (new SettingGroup())
                    ->setId('button')
                    ->setName('button')
                    ->setLabel(__('Button', 'mwc-core'))
                    ->setSettings([
                        $this->getColorSetting('#BADBFB', 'backgroundColor', __('Background color', 'mwc-core')),
                        $this->getColorSetting('#111111', 'color', __('Label color', 'mwc-core')),
                        $this->getFontFamilySetting(),
                        $this->getFontSizeSetting(14),
                    ]),
            ]);

        $footerGroup = (new SettingGroup())
            ->setId('footer')
            ->setName('footer')
            ->setLabel(__('Footer', 'mwc-core'))
            ->setSettings([
                $this->getColorSetting('#000000'),
                $this->getFontFamilySetting(),
                $this->getFontSizeSetting(13),
                (new EmailNotificationSetting())
                    ->setId('footerText')
                    ->setName('footerText')
                    ->setLabel(__('Footer text', 'mwc-core'))
                    ->setType(EmailNotificationSetting::TYPE_STRING)
                    ->setDefault('{{site_title}} &mdash; Built with WooCommerce')
                    ->setControl((new Control())->setType(Control::TYPE_TEXT)),
            ]);

        return [
            $containerGroup,
            $headerGroup,
            $bodyGroup,
            $footerGroup,
        ];
    }

    /**
     * Gets a color setting.
     *
     * @param string $default setting default value
     * @param string $id setting ID optional (defaults to 'color')
     * @param string $label setting label optional (defaults to 'Text color')
     * @return EmailNotificationSetting
     */
    private function getColorSetting(string $default, string $id = 'color', string $label = '') : EmailNotificationSetting
    {
        return
            (new EmailNotificationSetting())
                ->setId($id)
                ->setName($id)
                ->setLabel($label ?: __('Text color', 'mwc-core'))
                ->setType(EmailNotificationSetting::TYPE_STRING)
                ->setDefault($default)
                ->setControl((new Control())->setType(Control::TYPE_COLOR_PICKER));
    }

    /**
     * Gets a font family setting.
     *
     * @param string $default setting default value optional (defaults to 'Arial')
     * @param string $label setting label optional (defaults to 'Font family')
     * @return EmailNotificationSetting
     */
    private function getFontFamilySetting(string $default = 'Arial', string $label = '') : EmailNotificationSetting
    {
        $allowedFonts = $this->getAllowedFonts();

        return (new EmailNotificationSetting())
            ->setId('fontFamily')
            ->setName('fontFamily')
            ->setLabel($label ?: __('Font family', 'mwc-core'))
            ->setType(EmailNotificationSetting::TYPE_STRING)
            ->setOptions(array_values($allowedFonts))
            ->setDefault($default)
            ->setControl((new Control())
                ->setType(Control::TYPE_SELECT)
                ->setOptions($allowedFonts)
            );
    }

    /**
     * Gets a font size setting.
     *
     * @param int $default setting default value optional (defaults to 12')
     * @param string $id setting ID optional (defaults to 'fontSize')
     * @param string $label setting label optional (defaults to 'Font size')
     * @return EmailNotificationSetting
     */
    private function getFontSizeSetting(int $default, string $id = 'fontSize', string $label = '') : EmailNotificationSetting
    {
        $allowedFontSizes = $this->getAllowedFontSizes();

        return (new EmailNotificationSetting())
            ->setId($id)
            ->setName($id)
            ->setLabel($label ?: __('Font size', 'mwc-core'))
            ->setType(EmailNotificationSetting::TYPE_INTEGER)
            ->setOptions($allowedFontSizes)
            ->setDefault($default)
            ->setControl((new Control())
                ->setType(Control::TYPE_SELECT)
                ->setOptions(array_combine($allowedFontSizes, $allowedFontSizes))
            );
    }

    /**
     * Gets the allowed font families.
     *
     * @return string[]
     */
    protected function getAllowedFonts() : array
    {
        return [
            'Arial' => 'Arial',
            'Courier New' => 'Courier New',
            'Georgia' => 'Georgia',
            'Helvetica Neue' => 'Helvetica Neue',
            'Lucida Sans Unicode' => 'Lucida Sans Unicode, Lucida Grande',
            'Open Sans' => 'Open Sans',
            'Roboto' => 'Roboto',
            'Tahoma' => 'Tahoma',
            'Times New Roman' => 'Times New Roman',
            'Trebuchet MS' => 'Trebuchet MS',
            'Verdana' => 'Verdana',
        ];
    }

    /**
     * Gets the allowed font sizes.
     *
     * @return int[]
     */
    protected function getAllowedFontSizes() : array
    {
        return [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 22, 24, 26, 28, 30, 34, 38, 42, 46, 50, 58, 64, 72, 80];
    }

    /**
     * Gets preset values for the settings.
     *
     * @param string $name
     * @return array
     */
    public function getSettingsPresets(string $name) : array
    {
        $presets = [
            'woocommerce' => [
                'container' => [
                    'backgroundColor' => '#F7F7F7',
                ],
                'header'    => [
                    'image'           => [
                        'url' => '',
                    ],
                    'backgroundColor' => '#96588A',
                ],
                'body'      => [
                    'backgroundColor' => '#FFFFFF',
                    'text'            => [
                        'color' => '#3C3C3C',
                    ],
                ],
                'footer'    => [
                    'footerText' => '{{site_title}} &mdash; Built with WooCommerce',
                ],
            ],
        ];

        return ArrayHelper::get($presets, $name, []);
    }
}
