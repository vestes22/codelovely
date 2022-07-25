<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;

/**
 * The base request for all transaction API requests.
 *
 * @since 2.10.0
 */
abstract class AbstractTransactionRequest extends AbstractBusinessRequest
{
    /** @var string the transaction ID */
    protected $transactionId;

    /**
     * AbstractTransactionRequest constructor.
     *
     * @since 2.10.0
     *
     * @param string|null $transactionId
     *
     * @throws Exception
     */
    public function __construct(string $transactionId = null)
    {
        $this->transactionId = $transactionId;

        parent::__construct();
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
        $this->route = sprintf('transactions/%s%s', ! empty($this->transactionId) ? $this->transactionId.'/' : '', $this->route);

        return parent::setRoute();
    }
}
