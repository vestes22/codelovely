<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\VoidTransactionRequest;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\VoidTransaction;

/**
 * The void transaction adapter.
 */
class VoidTransactionAdapter implements DataSourceAdapterContract
{
    /** @var string voided response status */
    const RESPONSE_STATUS_VOIDED = 'VOIDED';

    /** @var VoidTransaction */
    protected $source;

    /**
     * Void transaction adapter constructor.
     *
     * @param VoidTransaction $transaction
     */
    public function __construct(VoidTransaction $transaction)
    {
        $this->source = $transaction;
    }

    /**
     * Converts a void transaction to a void transaction request.
     *
     * @return VoidTransactionRequest
     * @throws Exception
     */
    public function convertFromSource() : VoidTransactionRequest
    {
        return new VoidTransactionRequest($this->source->getRemoteParentId());
    }

    /**
     * Converts an HTTP response to a void transaction.
     *
     * @param Response|null $response
     * @return VoidTransaction
     */
    public function convertToSource(Response $response = null) : VoidTransaction
    {
        if (null === $response) {
            return $this->source;
        }

        $responseBody = $response->getBody() ?? [];

        $this->source->setResultCode((string) ArrayHelper::get($responseBody, 'processorResponse.statusCode', ''));

        if ($message = (string) ArrayHelper::get($responseBody, 'processorResponse.statusMessage', '')) {
            $this->source->setResultMessage($message);
        } else {
            $this->source->setResultMessage((string) ArrayHelper::get($responseBody, 'message', ''));
        }

        $this->source->setRemoteId((string) ArrayHelper::get($responseBody, 'id', ''));
        $this->source->setRemoteParentId((string) ArrayHelper::get($responseBody, 'parentId', ''));

        $totalAmount = (new CurrencyAmount())
            ->setAmount((int) ArrayHelper::get($responseBody, 'amounts.transactionAmount'))
            ->setCurrencyCode((string) ArrayHelper::get($responseBody, 'amounts.currency'));

        $this->source->setTotalAmount($totalAmount);

        $this->convertResponseStatus($responseBody);
        $this->convertParentType($responseBody);

        return $this->source;
    }

    /**
     * Converts the transaction parent type from the given response body data.
     *
     * @param array $responseBody
     */
    protected function convertParentType(array $responseBody)
    {
        // find the link data that matches this transaction's parentId
        $parentLinkValues = current(ArrayHelper::where(ArrayHelper::get($responseBody, 'links', []), function ($linkValues) use ($responseBody) {
            return ArrayHelper::get($responseBody, 'parentId') === ArrayHelper::get($linkValues, 'href');
        }));

        switch (ArrayHelper::get($parentLinkValues, 'rel')) {
            case 'CAPTURE':
                $parentType = 'capture';
                break;
            case 'REFUND':
                $parentType = 'refund';
                break;
            default:
                $parentType = 'payment';
        }

        $this->source->setParentType($parentType);
    }

    /**
     * Converts the response status to a normalized transaction status.
     *
     * Responses for voiding authorization transactions will have a straightforward VOIDED status.
     * However, if voiding a sale transaction (captured), then the response will have a DECLINED status with a voided=1 property.
     *
     * @param array $responseBody
     */
    protected function convertResponseStatus(array $responseBody)
    {
        if (
            self::RESPONSE_STATUS_VOIDED === ArrayHelper::get($responseBody, 'status') ||
            (
                PaymentTransactionAdapter::PAYMENT_ACTION_CHARGE === ArrayHelper::get($responseBody, 'action') &&
                (bool) ArrayHelper::get($responseBody, 'voided')
            )
        ) {
            $this->source->setStatus(new ApprovedTransactionStatus());
        } else {
            $this->source->setstatus(new DeclinedTransactionStatus());
        }
    }
}
