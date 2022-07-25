<?php

namespace GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Payments\Contracts\BankAccountTypeContract;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\BankAccountPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\BankAccounts\Types\CheckingBankAccountType;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\BankAccounts\Types\SavingsBankAccountType;
use WC_Payment_Token_ECheck;

/**
 * Bank account payment method adapter.
 *
 * Adapter to convert between WooCommerce eChecks payment tokens and native bank account payment method objects.
 *
 * @since 0.1.0
 */
class BankAccountPaymentMethodAdapter implements DataSourceAdapterContract
{
    /** @var string WooCommerce payment token meta data key to store a datetime string when the token was created */
    const CREATED_AT_META_KEY = 'created_at';

    /** @var string WooCommerce payment token meta data key to store a datetime string when the token was last updated */
    const UPDATED_AT_META_KEY = 'updated_at';

    /** @var string WooCommerce payment token meta data key to store the bank account type */
    const ACCOUNT_TYPE_META_KEY = 'account_type';

    /** @var WC_Payment_Token_ECheck WooCommerce eCheck payment method token */
    protected $source;

    /**
     * Bank account payment method adapter constructor.
     *
     * @since 0.1.0
     *
     * @param WC_Payment_Token_ECheck $token
     */
    public function __construct(WC_Payment_Token_ECheck $token)
    {
        $this->source = $token;
    }

    /**
     * Converts a WooCommerce eCheck payment token into a bank account native payment method.
     *
     * @since 0.1.0
     *
     * @return BankAccountPaymentMethod
     * @throws Exception
     */
    public function convertFromSource() : BankAccountPaymentMethod
    {
        $paymentMethod = (new BankAccountPaymentMethod())
            ->setId((int) $this->source->get_id())
            ->setProviderName((string) $this->source->get_gateway_id())
            ->setRemoteId((string) $this->source->get_token())
            ->setCustomerId((int) $this->source->get_user_id())
            ->setLastFour((string) $this->source->get_last4());

        if ($createdAt = $this->source->get_meta(self::CREATED_AT_META_KEY)) {
            $paymentMethod->setCreatedAt(new DateTime($createdAt));
        }
        if ($updatedAt = $this->source->get_meta(self::UPDATED_AT_META_KEY)) {
            $paymentMethod->setUpdatedAt(new DateTime($updatedAt));
        }

        if ($accountType = $this->convertAccountTypeFromSource()) {
            $paymentMethod->setType($accountType);
        }

        return $paymentMethod;
    }

    /**
     * Converts the source account type name to its associated type object.
     *
     * @since 0.1.0
     *
     * @return BankAccountTypeContract|null
     */
    protected function convertAccountTypeFromSource()
    {
        $checking = new CheckingBankAccountType();
        $savings  = new SavingsBankAccountType();

        switch ($this->source->get_meta(self::ACCOUNT_TYPE_META_KEY)) {
            case $checking->getName():
                return $checking;
            case $savings->getName():
                return $savings;
            default:
                return null;
        }
    }

    /**
     * Converts a bank account native payment method into a WooCommerce eCheck payment token.
     *
     * @since 0.1.0
     *
     * @param BankAccountPaymentMethod|null $paymentMethod
     * @return WC_Payment_Token_ECheck
     */
    public function convertToSource($paymentMethod = null) : WC_Payment_Token_ECheck
    {
        if (! $paymentMethod instanceof BankAccountPaymentMethod) {
            return $this->source;
        }

        $this->source->set_id($paymentMethod->getId());
        $this->source->set_gateway_id($paymentMethod->getProviderName());
        $this->source->set_token($paymentMethod->getRemoteId());
        $this->source->set_user_id($paymentMethod->getCustomerId());
        $this->source->set_last4($paymentMethod->getLastFour());

        $this->source->update_meta_data(self::CREATED_AT_META_KEY, $paymentMethod->getCreatedAt() ? $paymentMethod->getCreatedAt()->format('c') : '');
        $this->source->update_meta_data(self::UPDATED_AT_META_KEY, $paymentMethod->getUpdatedAt() ? $paymentMethod->getUpdatedAt()->format('c') : '');

        $this->source->update_meta_data(self::ACCOUNT_TYPE_META_KEY, $paymentMethod->getType() ? $paymentMethod->getType()->getName() : '');

        return $this->source;
    }
}
