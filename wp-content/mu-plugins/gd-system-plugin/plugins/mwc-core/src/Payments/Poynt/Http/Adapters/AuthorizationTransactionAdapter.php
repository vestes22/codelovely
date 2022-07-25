<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AuthorizationTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;

/**
 * The authorization transaction adapter.
 */
class AuthorizationTransactionAdapter implements DataSourceAdapterContract
{
    /** @var string successful response status */
    const RESPONSE_STATUS_SUCCESSFUL = 'Successful';

    /** @var AuthorizationTransaction */
    private $source;

    /**
     * Authorization transaction adapter constructor.
     *
     * @param AuthorizationTransaction $transaction
     */
    public function __construct(AuthorizationTransaction $transaction)
    {
        $this->source = $transaction;
    }

    /**
     * Not used.
     *
     * @return void
     */
    public function convertFromSource()
    {
    }

    /**
     * Converts an HTTP response into an authorization transaction object.
     *
     * @param Response $response
     * @return AuthorizationTransaction
     */
    public function convertToSource(Response $response = null) : AuthorizationTransaction
    {
        if (is_null($response)) {
            return $this->source;
        }

        $responseBody = $response->getBody() ?? [];

        $this->source->setResultCode((string) ArrayHelper::get($responseBody, 'processorResponse.statusCode', ''));
        $this->source->setResultMessage((string) ArrayHelper::get($responseBody, 'processorResponse.statusMessage', ''));

        $this->source->setRemoteId((string) ArrayHelper::get($responseBody, 'id', ''));
        $this->source->setRemoteCaptureId($this->findRemoteCaptureId($response));

        if (ArrayHelper::get($responseBody, 'processorResponse.status') === self::RESPONSE_STATUS_SUCCESSFUL) {
            $this->source->setStatus(new ApprovedTransactionStatus());
        } else {
            $this->source->setstatus(new DeclinedTransactionStatus());
        }

        return $this->source;
    }

    /**
     * Get the remote capture transaction id, if any.
     *
     * @return string|null
     */
    protected function findRemoteCaptureId(Response $response)
    {
        $responseBody = $response->getBody() ?? [];

        $captureTransactionLink = current(ArrayHelper::where(ArrayHelper::get($responseBody, 'links', []), function ($value) {
            return 'CAPTURE' === ArrayHelper::get($value, 'rel');
        }));

        return ArrayHelper::get($captureTransactionLink, 'href');
    }
}
