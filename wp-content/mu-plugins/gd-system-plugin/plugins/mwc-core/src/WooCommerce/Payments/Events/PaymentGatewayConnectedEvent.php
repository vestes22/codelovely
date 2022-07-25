<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events;

class PaymentGatewayConnectedEvent extends AbstractPaymentGatewayEvent
{
    /** @var string the name of the event action */
    protected $action = 'connect';
}
