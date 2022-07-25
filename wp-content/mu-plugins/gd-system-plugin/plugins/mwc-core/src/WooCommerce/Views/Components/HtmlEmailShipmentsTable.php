<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components;

class HtmlEmailShipmentsTable extends AbstractShipmentsTable
{
    /** @var string The name of the template that should be used to render this instance of the component */
    protected $templateName = 'emails/order/order-shipments.php';
}
