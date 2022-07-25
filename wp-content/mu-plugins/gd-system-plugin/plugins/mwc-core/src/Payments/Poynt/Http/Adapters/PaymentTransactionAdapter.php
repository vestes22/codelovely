<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Common\Models\Address;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\ChargeRequest;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\AmericanExpressCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\CreditCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DebitCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DinersClubCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DiscoverCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MaestroCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MastercardCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\VisaCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\DeclinedTransactionStatus;

/**
 * The payment transaction adapter.
 */
class PaymentTransactionAdapter implements DataSourceAdapterContract
{
    /** @var string identifier for authorization payments */
    const PAYMENT_ACTION_AUTHORIZE = 'AUTHORIZE';

    /** @var string identifier for charge payments */
    const PAYMENT_ACTION_CHARGE = 'SALE';

    /** @var string authorized response status */
    const RESPONSE_STATUS_AUTHORIZED = 'AUTHORIZED';

    /** @var string captured response status */
    const RESPONSE_STATUS_CAPTURED = 'CAPTURED';

    /** @var string declined response status */
    const RESPONSE_STATUS_DECLINED = 'DECLINED';

    /** @var array AVS actual result values mapped to transaction AVS result values */
    const AVS_RESULT_VALUES = [
        'A' => 'NO_MATCH',
        'E' => 'NOT_VERIFIED',
        'N' => 'NO_MATCH',
        'R' => 'NO_RESPONSE_FROM_CARD_ASSOCIATION',
        'U' => 'ISSUER_NOT_CERTIFIED',
        'Y' => 'MATCH',
        'Z' => 'NO_MATCH',
    ];

    /** @var PaymentTransaction */
    protected $source;

    /**
     * The payment transaction adapter constructor.
     *
     * @param PaymentTransaction $transaction
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->source = $transaction;
    }

    /**
     * Converts a payment transaction to a charge request.
     *
     * @return ChargeRequest
     * @throws Exception
     */
    public function convertFromSource() : ChargeRequest
    {
        $transactionTotal = $this->source->getTotalAmount();
        $transactionAmount = $transactionTotal ? $transactionTotal->getAmount() : 0;
        $transactionCurrency = $transactionTotal ? $transactionTotal->getCurrencyCode() : '';
        $paymentMethod = $this->source->getPaymentMethod();
        $paymentMethodId = $paymentMethod ? $paymentMethod->getRemoteId() : '';
        $tipAmount = $this->source->getTipAmount() ? $this->source->getTipAmount()->getAmount() : 0;
        $cashbackAmount = $this->source->getCashbackAmount() ? $this->source->getCashbackAmount()->getAmount() : 0;

        $customerPresenceStatus = 'virtual_terminal' === $this->source->getSource()
            ? 'VIRTUAL_TERMINAL_NOT_PRESENT'
            : 'ECOMMERCE';

        $transactionData = [
            'context'       => [
                'businessId' => Configuration::get('payments.poynt.businessId', ''),
                'sourceApp'  => Configuration::get('payments.poynt.api.source', ''),
            ],
            'amounts'       => [
                'transactionAmount' => $transactionAmount,
                'orderAmount'       => $transactionAmount - $tipAmount - $cashbackAmount,
                'tipAmount'         => $tipAmount,
                'cashbackAmount'    => $cashbackAmount,
                'currency'          => $transactionCurrency,
            ],
            'emailReceipt'  => false,
            'fundingSource' => [
                'cardToken'    => $paymentMethodId,
                'entryDetails' => [
                    'customerPresenceStatus' => $customerPresenceStatus,
                    'entryMode'              => 'KEYED',
                ],
            ],
            'notes'         => $this->source->getNotes() ?? '',
            'references'    => [
                [
                    'type'       => 'CUSTOM',
                    'customType' => 'POYNT_COLLECT',
                    'id'         => StringHelper::generateUuid4(),
                ],
            ],
        ];

        /** @var Order $order */
        if ($order = $this->source->getOrder()) {
            $adaptedBillingAddress = $this->getAdaptedAddress($order->getBillingAddress());

            if ($this->hasAdaptedAddress($adaptedBillingAddress)) {
                $transactionData['fundingSource']['verificationData'] = $this->getVerificationData($adaptedBillingAddress);
            }

            $adaptedShippingAddress = $this->getAdaptedAddress($order->getShippingAddress());

            if ($this->hasAdaptedAddress($adaptedShippingAddress)) {
                $transactionData['shippingAddress'] = $adaptedShippingAddress;
            }

            if ($emailAddress = $order->getEmailAddress()) {
                $transactionData['receiptEmailAddress'] = $emailAddress;
            }

            // scope adding the phone number to virtual terminal transactions
            // TODO: open this up to all transactions once we've confirmed that our handling of the format is sound {cwiseman 2021-10-19}
            if ('virtual_terminal' === $this->source->getSource() && $phoneNumber = $order->getBillingAddress()->getPhone()) {
                $transactionData['receiptPhone'] = $this->getAdaptedPhoneNumber($phoneNumber);
            }

            $transactionData['references'][] = [
                'type'       => 'CUSTOM',
                'customType' => 'EXTERNAL_ORDER_ID',
                'id'         => $order->getNumber(),
            ];

            $transactionData['references'][] = [
                'type'       => 'CUSTOM',
                'customType' => 'EXTERNAL_ORDER_URL',
                'id'         => get_admin_url(null, 'post.php?post='.$order->getId().'&action=edit'),
            ];

            if (Poynt::shouldPushOrderDetailsToPoynt($order)) {
                $poyntOrderId = StringHelper::generateUuid4();
                $transactionData['references'][] = [
                    'type' => 'POYNT_ORDER',
                    'id'   => $poyntOrderId,
                ];
                $wcOrder = OrdersRepository::get($order->getId());
                $wcOrder->update_meta_data('_poynt_order_remoteId', $poyntOrderId);
                $wcOrder->save();
            }
        }

        $transactionData['action'] = $this->source->isAuthOnly() ? self::PAYMENT_ACTION_AUTHORIZE : self::PAYMENT_ACTION_CHARGE;
        $transactionData['authOnly'] = $this->source->isAuthOnly();
        $transactionData['partialAuthEnabled'] = false;

        return (new ChargeRequest())->body($transactionData);
    }

    /**
     * Gets the verification data.
     *
     * @since 2.14.0
     *
     * @param array $adaptedAddress
     * @return array
     */
    private function getVerificationData(array $adaptedAddress) : array
    {
        return [
            'cardHolderBillingAddress' => $adaptedAddress,
        ];
    }

    /**
     * Converts an address to array format.
     *
     * @param Address $address
     * @return array
     */
    private function getAdaptedAddress(Address $address) : array
    {
        $lines = $address->getLines();

        return [
            'line1'       => $lines[0] ?? '',
            'line2'       => $lines[1] ?? '',
            'city'        => $address->getLocality(),
            'territory'   => $address->getAdministrativeDistricts()[0] ?? '',
            'postalCode'  => $address->getPostalCode(),
            'countryCode' => $address->getCountryCode(),
        ];
    }

    /**
     * Determines whether an adapted address is not empty.
     *
     * @param array $adaptedAddress
     * @return bool
     */
    private function hasAdaptedAddress(array $adaptedAddress) : bool
    {
        return 0 !== count(array_filter($adaptedAddress));
    }

    /**
     * Gets the adapter phone number from raw value.
     *
     * @param string $value
     * @return array
     */
    protected function getAdaptedPhoneNumber(string $value) : array
    {
        $areaCode = $ituCountryCode = '';
        $localNumber = preg_replace('/[^+0-9]/', '', $value);
        $countryCodes = ArrayHelper::flatten($this->getCountryCodes());

        // ensure the longest country codes get matched
        sort($countryCodes);

        // try and match a known country code
        foreach ($countryCodes as $countryCode) {
            if ($countryCode && StringHelper::startsWith($value, $countryCode)) {
                $ituCountryCode = $countryCode;
                break;
            }
        }

        // if there was a country code match, split it from the local number
        if ($ituCountryCode) {
            $localNumber = substr($localNumber, strlen($ituCountryCode));

            // if we know there is an area code based on the country & remaining digits, split it out
            if ('+1' === $ituCountryCode && strlen($localNumber) >= 7) {
                $areaCode = substr($localNumber, 0, 3);
                $localNumber = substr($localNumber, 3);
            }
        }

        return array_filter([
            'ituCountryCode'   => str_replace('+', '', $ituCountryCode),
            'areaCode'         => $areaCode,
            'localPhoneNumber' => $localNumber,
        ]);
    }

    /**
     * Gets the available country codes.
     *
     * @return array
     */
    protected function getCountryCodes() : array
    {
        $countryCodes = ($wcInstance = WooCommerceRepository::getInstance()) ? include($wcInstance->plugin_path().'/i18n/phone.php') : null;

        return $countryCodes ?: [];
    }

    /**
     * Converts an HTTP response to a payment transaction.
     *
     * @param Response $response
     * @return PaymentTransaction
     * @throws Exception
     */
    public function convertToSource(Response $response = null) : PaymentTransaction
    {
        if (null === $response) {
            return $this->source;
        }

        $responseBody = $response->getBody() ?? [];

        $this->source->setRemoteId((string) ArrayHelper::get($responseBody, 'id', ''));

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

        // if the transaction doesn't already have a payment method set, use the response data to generate one for credit cards
        if (! $this->source->getPaymentMethod() && 'CREDIT_DEBIT' === ArrayHelper::get($responseBody, 'fundingSource.type')) {
            $this->source->setPaymentMethod($this->adaptCardPaymentMethodToSource($responseBody));
        }

        $this->adaptResponseStatusToSource((string) ArrayHelper::get($responseBody, 'status', ''));

        $processorResponse = ArrayHelper::wrap(ArrayHelper::get($responseBody, 'processorResponse', []));

        $this->adaptResponseAvsResultToSource(ArrayHelper::wrap(ArrayHelper::get($processorResponse, 'avsResult', [])));

        $this->source->setResultCode((string) ArrayHelper::get($processorResponse, 'approvalCode', ''));
        $this->source->setResultMessage((string) ArrayHelper::get($processorResponse, 'statusMessage', ''));

        if ($createdAt = ArrayHelper::get($responseBody, 'createdAt')) {
            $this->source->setCreatedAt(new DateTime((string) $createdAt));
        }

        if ($updatedAt = ArrayHelper::get($responseBody, 'updatedAt')) {
            $this->source->setUpdatedAt(new DateTime((string) $updatedAt));
        }

        return $this->source;
    }

    /**
     * Adapts a Poynt API transaction response object to a core platform card
     * payment method.
     *
     * @param array $responseBody
     * @return CardPaymentMethod
     */
    protected function adaptCardPaymentMethodToSource(array $responseBody) : CardPaymentMethod
    {
        // TODO: Should this adapt code live elsewhere? E.g. CardPaymentMethodAdapter? however that works with a WC_Payment_Token_CC which we don't have for Poynt API transaction responses
        return (new CardPaymentMethod())
            ->setBrand($this->convertCardBrandFromSource(
                ArrayHelper::get($responseBody, 'fundingSource.card.type'),
                ArrayHelper::get($responseBody, 'fundingSource.debit')
            ))->setExpirationMonth(ArrayHelper::get($responseBody, 'fundingSource.card.expirationMonth'))
            ->setExpirationYear(ArrayHelper::get($responseBody, 'fundingSource.card.expirationYear'))
            ->setLastFour(ArrayHelper::get($responseBody, 'fundingSource.card.numberLast4'))
            ->setRemoteId(ArrayHelper::get($responseBody, 'fundingSource.card.cardId', ''));
    }

    /**
     * Adapts the response status to source.
     *
     * @param string $status
     */
    private function adaptResponseStatusToSource(string $status)
    {
        switch ($status) {
            case self::RESPONSE_STATUS_AUTHORIZED:
                $this->source->setStatus(new ApprovedTransactionStatus());
                $this->source->setAuthOnly(true);
                break;
            case self::RESPONSE_STATUS_CAPTURED:
                $this->source->setStatus(new ApprovedTransactionStatus());
                $this->source->setAuthOnly(false);
                break;
            case self::RESPONSE_STATUS_DECLINED:
            default:
                $this->source->setStatus(new DeclinedTransactionStatus());
                break;
        }
    }

    /**
     * Adapts the response actual AVS result value to the source transaction AVS result value.
     *
     * @param array $avsResult
     */
    private function adaptResponseAvsResultToSource(array $avsResult)
    {
        $value = 'NO_RESPONSE';
        $actualResult = ArrayHelper::get($avsResult, 'actualResult');

        foreach (static::AVS_RESULT_VALUES as $resultCode => $transactionResult) {
            if ($actualResult === $resultCode) {
                $value = $transactionResult;
                break;
            }
        }

        $this->source->setAvsResult($value);
    }

    /**
     * Converts a Poynt card type to a native card brand object.
     * See https://docs.poynt.com/api-reference/#model-card.
     *
     * Note: This method is largely duplicated from CardPaymentMethodAdapter
     *
     * @param string $cardType
     * @param bool $isDebit
     * @return CardBrandContract
     */
    private function convertCardBrandFromSource(string $cardType, bool $isDebit) : CardBrandContract
    {
        switch ($cardType) {
            case 'AMERICAN_EXPRESS':
                return new AmericanExpressCardBrand();
            case 'DINERS_CLUB':
                return new DinersClubCardBrand();
            case 'DISCOVER':
                return new DiscoverCardBrand();
            case 'MAESTRO':
                return new MaestroCardBrand();
            case 'MASTERCARD':
                return new MastercardCardBrand();
            case 'VISA':
                return new VisaCardBrand();
            case 'OTHER':
                // Note: there is an undocumented card.cardBrand object
                // with attributes scheme and displayName with values e.g.
                // 'VISA' and 'Visa' respectively, that might provide better values
                // here but we should learn more about it before relying on it.
                return $isDebit ? new DebitCardBrand() : new CreditCardBrand();
            default:
                return (new CreditCardBrand())
                    ->setName(strtolower($cardType))
                    ->setLabel($cardType);
        }
    }
}
