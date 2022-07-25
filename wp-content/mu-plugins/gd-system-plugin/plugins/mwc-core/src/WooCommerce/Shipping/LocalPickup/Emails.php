<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Shipping\LocalPickup;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Emails\ReadyForPickupEmail;

class Emails
{
    /**
     * Emails handler constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Adds the hooks to handle ready for pickup emails.
     *
     * @since x.y.x
     *
     * @throws Exception
     */
    public function addHooks()
    {
        Register::filter()
            ->setGroup('woocommerce_email_classes')
            ->setHandler([$this, 'addReadyForPickupEmail'])
            ->execute();
    }

    /**
     * Adds our email to the list of emails WooCommerce should load.
     *
     * @since 2.10.0
     * @internal
     *
     * @param array $emailClasses available email classes
     * @return array filtered available email classes
     * @throws Exception
     */
    public function addReadyForPickupEmail(array $emailClasses) : array
    {
        return ArrayHelper::insertAfter($emailClasses, ['ReadyForPickupEmail' => new ReadyForPickupEmail()], 'WC_Email_Customer_Completed_Order');
    }
}
