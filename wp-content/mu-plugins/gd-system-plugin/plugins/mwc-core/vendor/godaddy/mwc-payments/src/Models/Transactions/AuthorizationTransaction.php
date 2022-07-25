<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Transactions;

/**
 * Authorization transaction.
 *
 */
class AuthorizationTransaction extends AbstractTransaction
{
    /** @var string type */
    protected $type = 'authorization';

    /** @var string|null */
    protected $remoteCaptureId;

    /**
     * Sets the remote capture ID.
     *
     * @param string $value the remote capture id
     * @return AuthorizationTransaction
     */
    public function setRemoteCaptureId(string $value) : AuthorizationTransaction
    {
        $this->remoteCaptureId = $value;

        return $this;
    }

    /**
     * Gets the remote capture ID (if any).
     *
     * @return string|null the remote capture ID if set, otherwise null
     */
    public function getRemoteCaptureId()
    {
        return $this->remoteCaptureId;
    }
}
