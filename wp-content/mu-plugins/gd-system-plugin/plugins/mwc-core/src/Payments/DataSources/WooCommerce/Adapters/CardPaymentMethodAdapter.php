<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataSources\WooCommerce\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CardPaymentMethodAdapter as PaymentsCardPaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use WC_Payment_Token_CC;

/**
 * Card payment method adapter.
 *
 * Adapter to convert between WooCommerce credit card payment tokens and native card payment method objects.
 *
 * TODO: consider adding these changes to the adapter included in mwc-payments {@wvega 2021-06-01}
 */
class CardPaymentMethodAdapter extends PaymentsCardPaymentMethodAdapter
{
    /** @var string WooCommerce payment token meta data key to store a nickname for the token */
    const NICKNAME_META_KEY = 'nickname';

    /**
     * Converts a WooCommerce credit card payment token into a native card payment method.
     *
     * @return CardPaymentMethod
     * @throws Exception
     */
    public function convertFromSource() : CardPaymentMethod
    {
        /** @var CardPaymentMethod */
        $paymentMethod = parent::convertFromSource();

        if ($nickname = $this->source->get_meta(static::NICKNAME_META_KEY)) {
            $paymentMethod->setLabel($nickname);
        }

        return $paymentMethod;
    }

    /**
     * Converts a card native payment method into a WooCommerce credit card token.
     *
     * @param CardPaymentMethod|null $paymentMethod
     * @return WC_Payment_Token_CC
     */
    public function convertToSource($paymentMethod = null) : WC_Payment_Token_CC
    {
        $source = parent::convertToSource($paymentMethod);

        if (! $paymentMethod instanceof CardPaymentMethod) {
            return $source;
        }

        if ($nickname = $paymentMethod->getLabel()) {
            $source->update_meta_data(static::NICKNAME_META_KEY, $nickname);
        }

        return $source;
    }
}
