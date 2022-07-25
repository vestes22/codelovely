<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * Shipment validation failed exception.
 *
 * @since x.y.z
 */
class ShipmentValidationFailedException extends BaseException
{
    /** @var int exception code */
    protected $code = 400;

    /** @var string exception level */
    protected $level = 'error';
}
