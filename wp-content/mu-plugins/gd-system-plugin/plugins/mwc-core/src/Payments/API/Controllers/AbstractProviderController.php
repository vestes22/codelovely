<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\API\Controllers;

use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\API\Controllers\AbstractController;

/**
 * A base that extends AbstractController for handling provider routes.
 */
abstract class AbstractProviderController extends AbstractController implements ComponentContract
{
    /** @var string API route */
    protected $route = 'payments/providers/(?P<providerName>[a-zA-Z0-9_-]+)';
}
