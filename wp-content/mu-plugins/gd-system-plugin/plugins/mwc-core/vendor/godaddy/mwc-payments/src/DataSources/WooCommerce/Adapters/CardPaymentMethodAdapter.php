<?php

namespace GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
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
use WC_Payment_Token_CC;

/**
 * Card payment method adapter.
 *
 * Adapter to convert between WooCommerce credit card payment tokens and native card payment method objects.
 *
 * @since 0.1.0
 */
class CardPaymentMethodAdapter implements DataSourceAdapterContract
{
    /** @var string WooCommerce payment token meta data key to store a datetime string when the token was created */
    const CREATED_AT_META_KEY = 'created_at';

    /** @var string WooCommerce payment token meta data key to store a datetime string when the token was last updated */
    const UPDATED_AT_META_KEY = 'updated_at';

    /** @var string WooCommerce payment token meta data key to store the payment method's bank identification number */
    const BIN_META_KEY = 'bank_identification_number';

    /** @var WC_Payment_Token_CC WooCommerce credit card payment method token */
    protected $source;

    /**
     * Card payment method adapter constructor.
     *
     * @since 0.1.0
     *
     * @param WC_Payment_Token_CC $token
     */
    public function __construct(WC_Payment_Token_CC $token)
    {
        $this->source = $token;
    }

    /**
     * Converts a WooCommerce credit card payment token into a native card payment method.
     *
     * @since 0.1.0
     *
     * @return CardPaymentMethod
     * @throws Exception
     */
    public function convertFromSource() : CardPaymentMethod
    {
        $paymentMethod = (new CardPaymentMethod())
            ->setId((int) $this->source->get_id())
            ->setProviderName((string) $this->source->get_gateway_id())
            ->setRemoteId((string) $this->source->get_token())
            ->setCustomerId((int) $this->source->get_user_id())
            ->setBrand($this->convertCardBrandFromSource())
            ->setLastFour((string) $this->source->get_last4())
            ->setExpirationYear((string) $this->source->get_expiry_year())
            ->setExpirationMonth((string) $this->source->get_expiry_month());

        if ($createdAt = $this->source->get_meta(self::CREATED_AT_META_KEY)) {
            $paymentMethod->setCreatedAt(new DateTime($createdAt));
        }
        if ($updatedAt = $this->source->get_meta(self::UPDATED_AT_META_KEY)) {
            $paymentMethod->setUpdatedAt(new DateTime($updatedAt));
        }
        if ($bin = $this->source->get_meta(self::BIN_META_KEY)) {
            $paymentMethod->setBin((string) $bin);
        }

        return $paymentMethod;
    }

    /**
     * Converts a WooCommerce card type to a native card brand object.
     *
     * @since 0.1.0
     *
     * @return CardBrandContract
     */
    private function convertCardBrandFromSource() : CardBrandContract
    {
        $amex = new AmericanExpressCardBrand();
        $diners = new DinersClubCardBrand();
        $discover = new DiscoverCardBrand();
        $maestro = new MaestroCardBrand();
        $mastercard = new MastercardCardBrand();
        $visa = new VisaCardBrand();

        switch ($this->source->get_card_type()) {
            case $amex->getName() :
                return $amex;
            case $diners->getName() :
                return $diners;
            case $discover->getName() :
                return $discover;
            case $maestro->getName() :
                return $maestro;
            case $mastercard->getName() :
                return $mastercard;
            case $visa->getName() :
                return $visa;
            default :
                return StringHelper::contains($this->source->get_card_type(), ['debit', 'Debit', __('Debit', 'mwc-payments')]) ? new DebitCardBrand() : new CreditCardBrand();
        }
    }

    /**
     * Converts a card native payment method into a WooCommerce credit card token.
     *
     * @since 0.1.0
     *
     * @param CardPaymentMethod|null $paymentMethod
     * @return WC_Payment_Token_CC
     */
    public function convertToSource($paymentMethod = null) : WC_Payment_Token_CC
    {
        if (! $paymentMethod instanceof CardPaymentMethod) {
            return $this->source;
        }

        $this->source->set_id($paymentMethod->getId());
        $this->source->set_gateway_id($paymentMethod->getProviderName());
        $this->source->set_token($paymentMethod->getRemoteId());
        $this->source->set_user_id($paymentMethod->getCustomerId());
        $this->source->set_card_type($paymentMethod->getBrand() ? $paymentMethod->getBrand()->getName() : '');
        $this->source->set_last4($paymentMethod->getLastFour());
        $this->source->set_expiry_year($paymentMethod->getExpirationYear());
        $this->source->set_expiry_month($paymentMethod->getExpirationMonth());

        $this->source->update_meta_data(self::CREATED_AT_META_KEY, $paymentMethod->getCreatedAt() ? $paymentMethod->getCreatedAt()->format('c') : '');
        $this->source->update_meta_data(self::UPDATED_AT_META_KEY, $paymentMethod->getUpdatedAt() ? $paymentMethod->getUpdatedAt()->format('c') : '');

        $this->source->update_meta_data(self::BIN_META_KEY, $paymentMethod->getBin());

        return $this->source;
    }
}
