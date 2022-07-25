<?php

namespace GoDaddy\WordPress\MWC\Dashboard\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * Order not found exception.
 *
 * @since x.y.z
 */
class OrderNotFoundException extends BaseException
{
    /** @var int exception code */
    protected $code = 404;

    /** @var string exception level */
    protected $level = 'error';
}
