<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\CaptureTransactionRequest;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\CaptureTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;

/**
 * The capture transaction adapter.
 *
 * @since 2.10.0
 */
class CaptureTransactionAdapter implements DataSourceAdapterContract
{
    /** @var string captured response status */
    const RESPONSE_STATUS_CAPTURED = 'CAPTURED';

    /** @var CaptureTransaction */
    private $source;

    /**
     * Capture transaction adapter constructor.
     *
     * @since 2.10.0
     *
     * @param CaptureTransaction $transaction
     */
    public function __construct(CaptureTransaction $transaction)
    {
        $this->source = $transaction;
    }

    /**
     * Converts a capture transaction object into a capture transaction request.
     *
     * @since 2.10.0
     *
     * @see https://docs.poynt.com/api-reference/#model-transactionamounts
     *
     * @return CaptureTransactionRequest
     * @throws Exception
     */
    public function convertFromSource() : CaptureTransactionRequest
    {
        $transactionTotal = $this->source->getTotalAmount();
        $transactionAmount = $transactionTotal ? $transactionTotal->getAmount() : 0;
        $transactionCurrency = $transactionTotal ? $transactionTotal->getCurrencyCode() : '';
        $tipAmount = $this->source->getTipAmount() ? $this->source->getTipAmount()->getAmount() : 0;
        $cashbackAmount = $this->source->getCashbackAmount() ? $this->source->getCashbackAmount()->getAmount() : 0;

        return (new CaptureTransactionRequest($this->source->getRemoteParentId()))
            ->setBody([
                'amounts' => [
                    'currency'          => $transactionCurrency,
                    'orderAmount'       => $transactionAmount - $tipAmount - $cashbackAmount,
                    'tipAmount'         => $tipAmount,
                    'cashbackAmount'    => $cashbackAmount,
                    'transactionAmount' => $transactionAmount,
                ],
            ]);
    }

    /**
     * Converts an HTTP response into a capture transaction object.
     *
     * @since 2.10.0
     *
     * @param Response $response
     * @return CaptureTransaction
     */
    public function convertToSource(Response $response = null) : CaptureTransaction
    {
        if (is_null($response)) {
            return $this->source;
        }

        $responseBody = $response->getBody() ?? [];

        $this->source->setResultCode((string) ArrayHelper::get($responseBody, 'processorResponse.statusCode', ''));

        if ($message = (string) ArrayHelper::get($responseBody, 'processorResponse.statusMessage', '')) {
            $this->source->setResultMessage($message);
        } else {
            $this->source->setResultMessage((string) ArrayHelper::get($responseBody, 'message', ''));
        }

        $this->source->setRemoteId($this->getTransactionRemoteId($response) ?? '');
        $this->source->setRemoteParentId((string) ArrayHelper::get($responseBody, 'parentId', ''));

        $totalAmount = (new CurrencyAmount())
            ->setAmount(ArrayHelper::get($responseBody, 'amounts.transactionAmount'))
            ->setCurrencyCode(ArrayHelper::get($responseBody, 'amounts.currency'));
        $this->source->setTotalAmount($totalAmount);

        $tipAmount = (new CurrencyAmount())
            ->setAmount(ArrayHelper::get($responseBody, 'amounts.tipAmount', 0))
            ->setCurrencyCode(ArrayHelper::get($responseBody, 'amounts.currency'));
        $this->source->setTipAmount($tipAmount);

        $cashbackAmount = (new CurrencyAmount())
            ->setAmount(ArrayHelper::get($responseBody, 'amounts.cashbackAmount', 0))
            ->setCurrencyCode(ArrayHelper::get($responseBody, 'amounts.currency'));
        $this->source->setCashbackAmount($cashbackAmount);

        if (ArrayHelper::get($responseBody, 'status') === self::RESPONSE_STATUS_CAPTURED) {
            $this->source->setStatus(new ApprovedTransactionStatus());
        } else {
            $this->source->setstatus(new DeclinedTransactionStatus());
        }

        return $this->source;
    }

    /**
     * Reads the response and gets either a deep link transaction id or a top level transaction id.
     *
     * @since 2.10.0
     *
     * @param Response $response
     * @return string
     */
    private function getTransactionRemoteId(Response $response) : string
    {
        $responseBody = $response->getBody();
        $id = ArrayHelper::get($responseBody, 'id', '');
        $links = ArrayHelper::get($responseBody, 'links', []);
        $href = is_array($links) ? ArrayHelper::get(current($links), 'href') : null;

        return is_string($href) ? $href : (string) $id;
    }
}
