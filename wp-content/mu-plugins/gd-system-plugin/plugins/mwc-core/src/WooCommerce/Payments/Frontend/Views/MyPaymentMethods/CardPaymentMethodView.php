<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\MyPaymentMethods;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\Traits\RendersCardPaymentMethodTrait;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;

/**
 * The card payment method view at Account -> My Payment Methods.
 *
 * @method CardPaymentMethod getPaymentMethod()
 */
class CardPaymentMethodView extends PaymentMethodView
{
    use RendersCardPaymentMethodTrait;

    /**
     * Gets the title for the payment method.
     *
     * @return string
     */
    public function getTitle() : string
    {
        return $this->getNickname() ?: $this->getBrand();
    }

    /**
     * Gets the brand for the payment method.
     *
     * @return string
     */
    public function getBrand() : string
    {
        if (! $brand = $this->getPaymentMethod()->getBrand()) {
            return '';
        }

        return $brand->getLabel();
    }

    /**
     * Gets the HTML for the icon of the payment method.
     *
     * @return string
     */
    public function getIconHTML() : string
    {
        $url = $this->getIconUrl();

        if (! $url) {
            return '';
        }

        return '<img src="'.esc_url($url).'" title="'.esc_attr($this->getBrand()).'" width="40" height="25" style="width: 40px; height: 25px;" />';
    }

    /**
     * Gets the HTML for the details column in the payment methods table.
     *
     * @return string
     */
    public function getDetailsHtml() : string
    {
        return $this->getIconHTML().$this->getLastFourHtml();
    }
}
