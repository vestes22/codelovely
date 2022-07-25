<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events;

class PaymentGatewayEnabledEvent extends AbstractPaymentGatewayEvent
{
    /** @var string the name of the event action */
    protected $action = 'enable';
}
