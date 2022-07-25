<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

trait RendersCardPaymentMethodTrait
{
    /**
     * Gets the card icon URL.
     *
     * @return string
     */
    public function getIconUrl() : string
    {
        if (! $brand = $this->getPaymentMethod()->getBrand()) {
            return '';
        }

        try {
            return WordPressRepository::getAssetsUrl("images/payments/cards/{$brand->getName()}.svg");
        } catch (Exception $exception) {
            return '';
        }
    }

    /**
     * Gets the HTML for the last four representation of the card.
     *
     * @return string
     */
    public function getLastFourHtml() : string
    {
        if (! $lastFour = $this->getPaymentMethod()->getLastFour()) {
            return '';
        }

        return '<span class="lastFour">&bull; &bull; &bull; '.esc_html($lastFour).'</span>';
    }
}
