<?php

namespace GoDaddy\WordPress\MWC\Core\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Request;

/**
 * The event bridge request representation.
 *
 * @since 2.10.0
 */
class EventBridgeRequest extends Request
{
    /** @var string the ID of the site */
    public $siteId;

    /**
     * Sets the site ID.
     *
     * @param string $siteId the ID of the site
     *
     * @return self
     * @throws Exception
     * @since 2.10.0
     */
    public function setSiteId(string $siteId) : Request
    {
        $this->siteId = $siteId;

        $this->headers($this->headers);

        return $this;
    }

    /**
     * Sets Request headers.
     *
     * @since 2.10.0
     *
     * @param array|null $additionalHeaders
     * @return self
     * @throws Exception
     */
    public function headers($additionalHeaders = []) : Request
    {
        parent::headers(ArrayHelper::combine($additionalHeaders, ['X-Site-ID' => $this->siteId]));

        return $this;
    }
}
