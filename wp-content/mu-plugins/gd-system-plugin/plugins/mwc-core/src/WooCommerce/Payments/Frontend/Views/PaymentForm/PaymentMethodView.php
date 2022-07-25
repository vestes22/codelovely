<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\PaymentForm;

use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\AbstractPaymentMethodView;

/**
 * The payment method view at checkout.
 */
class PaymentMethodView extends AbstractPaymentMethodView
{
    /**
     * Gets the icon HTML.
     *
     * @return string
     */
    public function getIconHTML() : string
    {
        $url = $this->getIconUrl();

        if (! $url) {
            return '';
        }

        return '<img src="'.esc_url($url).'" width="30" height="20" style="width: 30px; height: 20px;" />';
    }

    /**
     * Renders the payment method.
     *
     * @param bool $isSelected
     */
    public function render(bool $isSelected)
    {
        $providerName = $this->getPaymentMethod()->getProviderName();
        $id = $this->getPaymentMethod()->getId(); ?>
        <input
            type="radio"
            id="mwc-payments-<?php echo esc_attr($providerName); ?>-payment-method-<?php echo esc_attr($id); ?>"
            name="mwc-payments-<?php echo esc_attr($providerName); ?>-payment-method-id"
            class="mwc-payments-<?php echo esc_attr($providerName); ?>-payment-method mwc-payments-payment-method"
            value="<?php echo esc_attr($id); ?>"
            <?php checked($isSelected); ?>
        />
        <label class="mwc-payments-payment-method" for="mwc-payments-<?php echo esc_attr($providerName); ?>-payment-method-<?php echo esc_attr($id); ?>">
            <span class="title">
                <?php $this->renderTitle(); ?>
            </span>
        </label>
        <?php
    }

    /**
     * Render the payment method's title.
     */
    protected function renderTitle()
    {
        ?>

        <?php if ($label = $this->getPaymentMethod()->getLabel()) : ?>
            <span class="label"><?php echo esc_html($label); ?></span>
        <?php endif; ?>

        <?php if ($iconHtml = $this->getIconHtml()) : ?>
            <span class="icon"><?php echo $iconHtml; ?></span>
        <?php endif; ?>

        <?php
    }
}
