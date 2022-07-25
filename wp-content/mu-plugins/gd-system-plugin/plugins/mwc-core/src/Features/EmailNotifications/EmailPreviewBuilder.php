<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications;

use GoDaddy\WordPress\MWC\Core\Email\RenderableEmail;

/**
 * A builder for converting email notification definitions into email objects for user preview purposes.
 */
class EmailPreviewBuilder extends AbstractEmailBuilder
{
    /**
     * Gets the email notification (preview) data.
     *
     * @return array
     */
    protected function getEmailNotificationData() : array
    {
        return $this->emailNotification->getPreviewData();
    }

    /**
     * Gets a new email instance based on the set email notification.
     *
     * @return RenderableEmail
     */
    protected function getNewInstance() : RenderableEmail
    {
        return (new RenderableEmail(''))
            ->setSubject($this->emailNotification->getSubject() ?? '');
    }
}
