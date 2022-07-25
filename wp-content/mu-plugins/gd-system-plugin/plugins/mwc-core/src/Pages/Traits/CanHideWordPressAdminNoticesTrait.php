<?php

namespace GoDaddy\WordPress\MWC\Core\Pages\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * A trait hiding WordPress admin notices.
 */
trait CanHideWordPressAdminNoticesTrait
{
    /**
     * Opens div container to wrap admin notices to hide.
     *
     * @internal
     */
    public function openNoticesContainer()
    {
        ?>
        <style>
            #mwc-hidden-notices, .wpaas-notice {
                position: absolute !important;
                display: none !important;
                width: 0;
                height: 0;
                overflow: hidden !important;
            }
        </style>

        <div id="mwc-hidden-notices">
        <div class="wp-header-end"></div>
        <?php
    }

    /**
     * Closes div container that wraps admin notices.
     *
     * @internal
     */
    public function closeNoticesContainer()
    {
        ?>
        </div>
        <?php
    }

    /**
     * Registers necessary hooks to hide admin notices.
     *
     * @throws Exception
     */
    public function hideAdminNotices()
    {
        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'openNoticesContainer'])
            ->setPriority(-PHP_INT_MAX)
            ->execute();

        Register::action()
            ->setGroup('admin_notices')
            ->setHandler([$this, 'closeNoticesContainer'])
            ->setPriority(PHP_INT_MAX)
            ->execute();
    }
}
