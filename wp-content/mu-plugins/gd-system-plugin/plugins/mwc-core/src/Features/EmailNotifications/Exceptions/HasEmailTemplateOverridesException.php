<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions;

use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;

/**
 * An exception thrown to indicate that a site has email template overrides and hasn't enabled
 * the Email Notifications feature.
 */
class HasEmailTemplateOverridesException extends BaseException
{
    /** @var int HTTP status code */
    protected $code = 400;
}
