<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * The base request for all Resource-based API requests. This class can be
 * extended by concrete implementations of resource-based API requests that
 * require an id, e.g. PUT, as well as those that don't, e.g. POST.
 */
abstract class AbstractResourceRequest extends AbstractBusinessRequest
{
    /** @var string the resource ID */
    protected $resourceId;

    /** @var string the plural name of the resource, e.g. 'orders' */
    protected $resourcePlural;

    /**
     * AbstractResourceRequest constructor.
     *
     * @param string the plural name of the resource, e.g. 'orders'
     * @param string|null $resourceId
     *
     * @throws Exception
     */
    public function __construct(string $resourcePlural, string $resourceId = null)
    {
        $this->resourcePlural = $resourcePlural;
        $this->resourceId = $resourceId;

        parent::__construct();
    }

    /**
     * Sets the route.
     *
     * @return self
     */
    protected function setRoute() : AbstractBusinessRequest
    {
        $this->route = sprintf('%s/%s%s', $this->resourcePlural, ! empty($this->resourceId) ? $this->resourceId.'/' : '', $this->route);

        return parent::setRoute();
    }
}
