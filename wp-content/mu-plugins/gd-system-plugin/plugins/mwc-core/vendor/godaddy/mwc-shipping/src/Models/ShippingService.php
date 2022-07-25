<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * Represents a shipping service.
 *
 * @since 0.1.0
 */
class ShippingService extends AbstractModel
{
    use HasLabelTrait;
}
