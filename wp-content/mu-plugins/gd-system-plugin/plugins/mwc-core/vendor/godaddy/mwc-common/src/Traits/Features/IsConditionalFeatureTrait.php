<?php

namespace GoDaddy\WordPress\MWC\Common\Traits\Features;

/**
 * A trait to help loading features conditionally.
 *
 * @since 3.4.1
 */
trait IsConditionalFeatureTrait
{
    /**
     * Determines whether a feature should be loaded.
     *
     * @since 3.4.1
     *
     * @return bool returns true by default, implementations using this trait may override this
     */
    public static function shouldLoadConditionalFeature() : bool
    {
        return true;
    }
}
