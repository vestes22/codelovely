<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\BankAccounts\Types;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\BankAccountTypeContract;

/**
 * Checking bank account type.
 */
final class CheckingBankAccountType implements BankAccountTypeContract
{
    use HasLabelTrait;

    /**
     * Checking bank account type constructor.
     */
    public function __construct()
    {
        $this->setName('checking')
            ->setLabel(__('Checking Account', 'mwc-payments'));
    }
}
