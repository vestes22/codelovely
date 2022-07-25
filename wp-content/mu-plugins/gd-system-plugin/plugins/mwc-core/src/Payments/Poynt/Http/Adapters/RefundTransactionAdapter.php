<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\RefundTransactionRequest;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;

/**
 * The refund transaction adapter.
 *
 * @since 2.10.0
 */
class RefundTransactionAdapter implements DataSourceAdapterContract
{
    /** @var string refunded response status */
    const RESPONSE_STATUS_REFUNDED = 'REFUNDED';

    /** @var RefundTransaction */
    private $source;

    /**
     * Refund transaction adapter constructor.
     *
     * @since 2.10.0
     *
     * @param RefundTransaction $transaction
     */
    public function __construct(RefundTransaction $transaction)
    {
        $this->source = $transaction;
    }

    /**
     * Converts a refund transaction object into a refund transaction request.
     *
     * @since 2.10.0
     *
     * @return RefundTransactionRequest
     * @throws Exception
     */
    public function convertFromSource() : RefundTransactionRequest
    {
        $transactionTotal = $this->source->getTotalAmount();
        $transactionAmount = $transactionTotal ? $transactionTotal->getAmount() : 0;
        $transactionCurrency = $transactionTotal ? $transactionTotal->getCurrencyCode() : '';

        return (new RefundTransactionRequest())
            ->body([
                'action'   => 'REFUND',
                'parentId' => $this->source->getRemoteParentId(),
                'id'       => StringHelper::generateUuid4(),
                'context'  => [
                    'businessId' => Configuration::get('payments.poynt.businessId', ''),
                    'sourceApp'  => Configuration::get('payments.poynt.api.source', ''),
                ],
                'fundingSource' => [
                    'type' => 'CREDIT_DEBIT',
                ],
                'amounts' => [
                    'transactionAmount' => $transactionAmount,
                    'orderAmount'       => $transactionAmount,
                    'currency'          => $transactionCurrency,
                ],
                'notes' => $this->source->getNotes() ?? '',
            ]);
    }

    /**
     * Converts an HTTP response into a refund transaction object.
     *
     * @since 2.10.0
     *
     * @param Response $response
     * @return RefundTransaction
     * @throws Exception
     */
    public function convertToSource(Response $response = null) : RefundTransaction
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

        if (self::RESPONSE_STATUS_REFUNDED === ArrayHelper::get($responseBody, 'status')) {
            $this->source->setStatus(new ApprovedTransactionStatus());
        } else {
            $this->source->setStatus(new DeclinedTransactionStatus());
        }

        if ($createdAt = ArrayHelper::get($responseBody, 'createdAt')) {
            $this->source->setCreatedAt(new DateTime((string) $createdAt));
        }
        if ($updatedAt = ArrayHelper::get($responseBody, 'updatedAt')) {
            $this->source->setUpdatedAt(new DateTime((string) $updatedAt));
        }

        $totalAmount = (new CurrencyAmount())
            ->setAmount(ArrayHelper::get($responseBody, 'amounts.transactionAmount'))
            ->setCurrencyCode(ArrayHelper::get($responseBody, 'amounts.currency'));
        $this->source->setTotalAmount($totalAmount);

        return $this->source;
    }
}
