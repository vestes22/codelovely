<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\TokenizeRequest;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\AmericanExpressCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\CreditCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DinersClubCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\DiscoverCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MaestroCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\MastercardCardBrand;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands\VisaCardBrand;

/**
 * The card payment method adapter.
 *
 * @since 2.10.0
 */
class CardPaymentMethodAdapter implements DataSourceAdapterContract
{
    /** @var CardPaymentMethod */
    protected $source;

    /**
     * Card payment method adapter constructor.
     *
     * @since 2.10.0
     *
     * @param CardPaymentMethod $paymentMethod
     */
    public function __construct(CardPaymentMethod $paymentMethod)
    {
        $this->source = $paymentMethod;
    }

    /**
     * Converts a card payment method to a tokenization request.
     *
     * @since 2.10.0
     *
     * @return TokenizeRequest
     */
    public function convertFromSource() : TokenizeRequest
    {
        return (new TokenizeRequest())
            ->body([
                'nonce' => $this->source->getRemoteId(),
            ]);
    }

    /**
     * Converts to Data Source format.
     *
     * @since 2.10.0
     *
     * @param Response $response
     *
     * @return CardPaymentMethod
     * @throws Exception
     */
    public function convertToSource(Response $response = null) : CardPaymentMethod
    {
        if (is_null($response)) {
            return $this->source;
        }

        $responseBody = $response->getBody() ?? [];

        if (ArrayHelper::get($responseBody, 'status', '') !== 'ACTIVE') {
            throw new Exception('Card payment status is not active.');
        }

        if (ArrayHelper::get($responseBody, 'paymentToken', '') === '') {
            throw new Exception('Card payment token is not present in response.');
        }

        $responseBody = $response->getBody() ?? [];
        $cardData = ArrayHelper::get($responseBody, 'card', []);

        return $this->source->setRemoteId((string) ArrayHelper::get($responseBody, 'paymentToken', ''))
                            ->setBrand($this->normalizeCardBrand((string) ArrayHelper::get($cardData, 'type', '')))
                            ->setLastFour((string) ArrayHelper::get($cardData, 'numberLast4', ''))
                            ->setExpirationMonth(str_pad((string) ArrayHelper::get($cardData, 'expirationMonth', ''), 2, '0', STR_PAD_LEFT))
                            ->setExpirationYear((string) ArrayHelper::get($cardData, 'expirationYear', ''));
    }

    /**
     * Normalizes card brand received from API to one of our internal classes.
     *
     * @since 2.10.0
     *
     * @param string $brand
     * @return CardBrandContract
     */
    private function normalizeCardBrand(string $brand) : CardBrandContract
    {
        $cardBrands = [
            'VISA' => new VisaCardBrand(),
            'MASTERCARD' => new MastercardCardBrand(),
            'DISCOVER' => new DiscoverCardBrand(),
            'MAESTRO' => new MaestroCardBrand(),
            'DINERS_CLUB' => new DinersClubCardBrand(),
            'AMERICAN_EXPRESS' => new AmericanExpressCardBrand(),
            'JCB' => new CreditCardBrand(),
            'UNIONPAY' => new CreditCardBrand(),
        ];

        return $cardBrands[$brand] ?? new CreditCardBrand();
    }
}
