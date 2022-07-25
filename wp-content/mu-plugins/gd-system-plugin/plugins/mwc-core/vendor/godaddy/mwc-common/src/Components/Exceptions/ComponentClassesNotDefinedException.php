<?php

namespace GoDaddy\WordPress\MWC\Common\Components\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;

/**
 * An exception triggered when a class that uses the HasComponentsTrait didn't define a list of component classes to load.
 *
 * @since x.y.z
 */
class ComponentClassesNotDefinedException extends SentryException
{
}
