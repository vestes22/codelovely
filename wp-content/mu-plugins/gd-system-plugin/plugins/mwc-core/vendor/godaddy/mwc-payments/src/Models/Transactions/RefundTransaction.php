<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Transactions;

/**
 * Refund transaction.
 *
 * @since 0.1.0
 */
class RefundTransaction extends AbstractTransaction
{
    /** @var string|null reason for refund */
    private $reason;

    /** @var string type */
    protected $type = 'refund';

    /**
     * Gets the refund reason, if present.
     *
     * @since 0.1.0
     *
     * @return string|null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Sets the refund reason.
     *
     * @since 0.1.0
     *
     * @param string $reason
     * @return RefundTransaction
     */
    public function setReason(string $reason) : RefundTransaction
    {
        $this->reason = $reason;

        return $this;
    }
}
