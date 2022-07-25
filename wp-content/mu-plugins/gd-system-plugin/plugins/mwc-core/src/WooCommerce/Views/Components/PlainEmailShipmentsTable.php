<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components;

class PlainEmailShipmentsTable extends AbstractShipmentsTable
{
    /** @var string The name of the template that should be used to render this instance of the component */
    protected $templateName = 'emails/plain/order/order-shipments.php';
}
