<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders;

use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use WC_Emails;

/**
 * A provider for email notifications to handle data for site-related merge tags.
 *
 * @see WC_Emails::replace_placeholders() for reference
 */
class SiteDataProvider implements DataProviderContract
{
    /**
     * Gets site data.
     *
     * @return array
     */
    public function getData() : array
    {
        $domain = SiteRepository::getDomain();

        return [
            'site_address'  => $domain,
            'site_language' => SiteRepository::getLanguage(),
            'site_title'    => SiteRepository::getTitle(),
            'site_url'      => $domain,
        ];
    }

    /**
     * Gets site placeholders.
     *
     * @return string[]
     */
    public function getPlaceholders() : array
    {
        return [
            'site_title',
            'site_url',
        ];
    }

    /**
     * Gets site preview data.
     *
     * @return array
     */
    public function getPreviewData() : array
    {
        return $this->getData();
    }
}
