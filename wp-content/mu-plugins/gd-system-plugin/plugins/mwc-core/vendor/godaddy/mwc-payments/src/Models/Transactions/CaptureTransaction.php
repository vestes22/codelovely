<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Transactions;

use GoDaddy\WordPress\MWC\Payments\Traits\HasCashbackAmountTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\HasTipAmountTrait;

/**
 * Capture transaction.
 *
 * @since 0.1.0
 */
class CaptureTransaction extends AbstractTransaction
{
    use HasCashbackAmountTrait;
    use HasTipAmountTrait;

    /** @var string type */
    protected $type = 'capture';
}
