<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Events\PageViewEvent;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Onboarding;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPaymentsGateway;

/**
 * WooCommerce Payments Settings page view event class.
 *
 * @since 2.10.0
 */
class PaymentSettingsPageViewEvent extends PageViewEvent
{
    /**
     * Gets the data for the event.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getData() : array
    {
        $data = parent::getData();
        ArrayHelper::set($data, 'goDaddyPayments.isActive', GoDaddyPaymentsGateway::isActive());
        ArrayHelper::set($data, 'goDaddyPayments.onboardingStatus', Onboarding::getStatus());

        return $data;
    }
}
