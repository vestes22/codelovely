<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\BankAccounts\Types;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\BankAccountTypeContract;

/**
 * Savings bank account type.
 */
final class SavingsBankAccountType implements BankAccountTypeContract
{
    use HasLabelTrait;

    /**
     * Savings bank account type constructor.
     */
    public function __construct()
    {
        $this->setName('savings')
            ->setLabel(__('Savings Account', 'mwc-payments'));
    }
}
