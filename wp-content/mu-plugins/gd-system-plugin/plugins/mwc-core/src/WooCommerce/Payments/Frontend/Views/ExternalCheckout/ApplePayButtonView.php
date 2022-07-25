<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\ExternalCheckout;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\ApplePayGateway;

/**
 * View for displaying an Apple Pay button.
 */
class ApplePayButtonView extends AbstractButtonView
{
    /**
     * Determines whether the button is available for the given context.
     *
     * @param string $context one of the configured Apple Pay enabled pages
     * @return bool
     * @throws Exception
     */
    public function isAvailable(string $context): bool
    {
        return true === Configuration::get('payments.applePay.enabled')
            && ArrayHelper::contains(ArrayHelper::wrap(Configuration::get('payments.applePay.enabledPages', [])), $context)
            && Poynt::isConnected()
            && ApplePayGateway::isActive();
    }

    /**
     * Outputs the Apple Pay button element.
     *
     * @link https://developer.apple.com/documentation/apple_pay_on_the_web
     */
    public function render()
    {
        ?>
        <div class="<?php echo esc_attr(implode(' ', $this->getButtonClasses())); ?>" lang="<?php echo esc_attr($this->getButtonLanguage()); ?>" style="height: <?php echo esc_attr((string) $this->getButtonHeight()); ?>px;"><button><?php echo $this->getButtonContent(); ?></button></div>
        <?php
    }

    /**
     * Gets the locale for the Apple Pay button.
     *
     * @link https://developer.apple.com/documentation/apple_pay_on_the_web/displaying_apple_pay_buttons_using_css/localizing_apple_pay_buttons_using_css
     * @NOTE if the locale is invalid or unsupported by Apple, it should automatically have the browser default to English
     *
     * @return string
     */
    protected function getButtonLanguage() : string
    {
        return substr(WordPressRepository::getLocale(), 0, 2);
    }

    /**
     * Gets the button content (text and logo).
     *
     * @link https://developer.apple.com/design/human-interface-guidelines/apple-pay/overview/buttons-and-marks/
     *
     * @return string
     */
    protected function getButtonContent() : string
    {
        $open = '<span class="text">';
        $close = '</span>';
        $logo = '<span class="logo"></span><span class="no-logo">&nbsp;Apple Pay</span>';

        switch (strtoupper(Configuration::get('payments.applePay.buttonType', ''))) {
            case ApplePayGateway::BUTTON_TYPE_BOOK:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sBook with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_BUY:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sBuy with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_CHECKOUT:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sCheck out with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_CONTINUE:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sContinue with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_CONTRIBUTE:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sContribute with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_DONATE:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sDonate with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_ORDER:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sOrder with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_PAY:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sPay with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_RENT:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sRent with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_SUPPORT:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sSupport with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_TIP:
                /* translators: Placeholders: %1$s - opening <span> tag, %2$s - closing </span> tag, %3$s HTML element with "Apple Pay" logo */
                $text = __('%1$sTip with%2$s%3$s', 'mwc-core');
                break;
            case ApplePayGateway::BUTTON_TYPE_PLAIN:
            default:
                $open = $close = '';
                $text = '%1$s%2$s%3$s';
                break;
        }

        return trim(sprintf(esc_html($text), $open, $close, $logo));
    }

    /**
     * Gets the CSS classes of the button element.
     *
     * @link https://developer.apple.com/documentation/apple_pay_on_the_web/displaying_apple_pay_buttons_using_css
     *
     * @return string[]
     */
    protected function getButtonClasses() : array
    {
        $classes = ['mwc-payments-apple-pay-button', 'apple-pay-button'];

        switch (strtoupper(Configuration::get('payments.applePay.buttonStyle', ''))) {
            case ApplePayGateway::BUTTON_STYLE_BLACK:
                $classes[] = 'apple-pay-button-black';
                break;
            case ApplePayGateway::BUTTON_STYLE_WHITE:
                $classes[] = 'apple-pay-button-white';
                break;
            case ApplePayGateway::BUTTON_STYLE_WHITE_WITH_LINE:
                $classes[] = 'apple-pay-button-white-with-line';
                break;
        }

        $buttonType = strtoupper(Configuration::get('payments.applePay.buttonType', ''));

        if (ApplePayGateway::BUTTON_TYPE_PLAIN === $buttonType) {
            $classes[] = 'apple-pay-button-plain';
        } else {
            $classes[] = 'apple-pay-button-with-text';

            switch ($buttonType) {
                case ApplePayGateway::BUTTON_TYPE_BOOK:
                    $classes[] = 'apple-pay-button-book';
                    break;
                case ApplePayGateway::BUTTON_TYPE_BUY:
                    $classes[] = 'apple-pay-button-buy';
                    break;
                case ApplePayGateway::BUTTON_TYPE_CHECKOUT:
                    $classes[] = 'apple-pay-button-checkout';
                    break;
                case ApplePayGateway::BUTTON_TYPE_CONTINUE:
                    $classes[] = 'apple-pay-button-continue';
                    break;
                case ApplePayGateway::BUTTON_TYPE_CONTRIBUTE:
                    $classes[] = 'apple-pay-button-contribute';
                    break;
                case ApplePayGateway::BUTTON_TYPE_DONATE:
                    $classes[] = 'apple-pay-button-donate';
                    break;
                case ApplePayGateway::BUTTON_TYPE_ORDER:
                    $classes[] = 'apple-pay-button-order';
                    break;
                case ApplePayGateway::BUTTON_TYPE_PAY:
                    $classes[] = 'apple-pay-button-pay';
                    break;
                case ApplePayGateway::BUTTON_TYPE_RENT:
                    $classes[] = 'apple-pay-button-rent';
                    break;
                case ApplePayGateway::BUTTON_TYPE_SUPPORT:
                    $classes[] = 'apple-pay-button-support';
                    break;
                case ApplePayGateway::BUTTON_TYPE_TIP:
                    $classes[] = 'apple-pay-button-tip';
                    break;
            }
        }

        return $classes;
    }

    /**
     * Gets the button height.
     *
     * @return int
     */
    protected function getButtonHeight() : int
    {
        // Apple Pay wants a minimum of 30 and a maximum of 64 pixels for the button height; the default value is as per setting default
        return max(ApplePayGateway::BUTTON_HEIGHT_MIN, min(ApplePayGateway::BUTTON_HEIGHT_MAX, (int) Configuration::get('payments.applePay.buttonHeight', ApplePayGateway::BUTTON_HEIGHT_DEFAULT)));
    }
}
