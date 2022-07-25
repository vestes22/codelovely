<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\MyPaymentMethods;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\AbstractPaymentMethodView;

/**
 * The payment method view at My Account -> Payment Methods.
 */
class PaymentMethodView extends AbstractPaymentMethodView
{
    /**
     * Gets the title for the payment method.
     *
     * @return string
     */
    public function getTitle() : string
    {
        return $this->getNickname();
    }

    /**
     * Gets the nickname for the payment method.
     *
     * @return string
     */
    public function getNickname() : string
    {
        return $this->getPaymentMethod()->getLabel() ?: '';
    }

    /**
     * Gets the HTML for the title column in the payment methods table.
     *
     * @return string
     */
    public function getTitleHtml() : string
    {
        $html = '<div class="view">'.esc_html($this->getTitle()).'</div>';

        // add the edit context input
        $html .= '<div class="edit" style="display:none;">';
        $html .= '<input type="text" class="nickname" name="nickname" value="'.esc_attr($this->getNickname()).'" placeholder="'.esc_attr__('Nickname', 'mwc-core').'" />';
        $html .= '<input type="hidden" name="token-id" value="'.esc_attr($this->getPaymentMethod()->getId()).'" data-mwc-core-token="yes" />';
        $html .= '</div>';

        return $html;
    }

    /**
     * Gets the HTML for the details column in the payment methods table.
     *
     * @return string
     */
    public function getDetailsHtml() : string
    {
        return '';
    }

    /**
     * Gets the HTML for the default column in the payment methods table.
     *
     * @param bool $isDefault
     * @return string
     */
    public function getDefaultHtml(bool $isDefault) : string
    {
        return $isDefault ? '<mark class="default">'.esc_html__('Default', 'mwc-core').'</mark>' : '';
    }
}
