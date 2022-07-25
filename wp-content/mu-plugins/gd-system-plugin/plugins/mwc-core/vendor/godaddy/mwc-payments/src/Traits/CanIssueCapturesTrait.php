<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Payments\Events\CaptureTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\CaptureTransaction;

/**
 * Can Issue Captures Trait.
 *
 * @since 0.1.0
 */
trait CanIssueCapturesTrait
{
    use AdaptsRequestsTrait;

    /** @var string Capture Transaction Adapter class */
    protected $captureTransactionAdapter;

    /**
     * Issues capture transaction request.
     *
     * @since 0.1.0
     *
     * @param CaptureTransaction $transaction
     *
     * @return CaptureTransaction
     * @throws Exception
     */
    public function capture(CaptureTransaction $transaction) : CaptureTransaction
    {
        $request = $this->doAdaptedRequest($transaction, new $this->captureTransactionAdapter($transaction));

        Events::broadcast(new CaptureTransactionEvent($request));

        return $request;
    }
}
