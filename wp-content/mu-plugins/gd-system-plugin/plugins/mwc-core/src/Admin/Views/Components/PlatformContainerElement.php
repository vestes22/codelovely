<?php

namespace GoDaddy\WordPress\MWC\Core\Admin\Views\Components;

class PlatformContainerElement
{
    /**
     * Flag whether the element rendered or not.
     *
     * @var bool
     */
    protected static $elementRendered = false;

    /**
     * May renders the element if not already rendered.
     */
    public static function renderIfNotRendered()
    {
        if (static::$elementRendered) {
            return;
        }

        static::$elementRendered = true;

        echo '<div id="mwc-platform-app"></div>';
    }
}
