<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;

/**
 * The base request for all business-specific API requests.
 *
 * @since 2.10.0
 */
abstract class AbstractBusinessRequest extends Request
{
    /** @var string the business ID */
    protected $businessId;

    /**
     * AbstractBusinessRequest constructor.
     *
     * @since 2.10.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this
            ->setBusinessId()
            ->setRoute();

        parent::__construct();
    }

    /**
     * Sets the business ID.
     *
     * @since 2.10.0
     *
     * @return AbstractBusinessRequest
     *
     * @throws Exception
     */
    protected function setBusinessId() : AbstractBusinessRequest
    {
        $this->businessId = Configuration::get('payments.poynt.businessId');

        return $this;
    }

    /**
     * Sets the route.
     *
     * @since 2.10.0
     *
     * @return self
     */
    protected function setRoute() : AbstractBusinessRequest
    {
        $this->route = sprintf('businesses/%s/%s', $this->businessId, $this->route);

        return $this;
    }
}
