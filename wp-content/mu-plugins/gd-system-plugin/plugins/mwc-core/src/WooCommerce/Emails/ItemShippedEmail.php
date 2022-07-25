<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Emails;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use WC_Email;
use WC_Order;

/**
 * Item Shipped Email class.
 *
 * @since 2.10.0
 */
class ItemShippedEmail extends WC_Email
{
    /** @var string the email ID */
    public $id = 'mwc_item_shipped_email';

    /** @var bool true when the email notification is sent to customers */
    protected $customer_email = true;

    /**
     * Item Shipped Email constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->title = __('Item shipped', 'mwc-core');
        $this->description = __('Item shipped emails are sent to customers when tracking information is added for one or more items in their orders.', 'mwc-core');

        $this->addHooks();
    }

    /**
     * Adds the hook to trigger the email when tracking information is added.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::action()
                ->setGroup('mwc_shipment_tracking_information_added')
                ->setHandler([$this, 'trigger'])
                ->execute();
    }

    /**
     * Gets the email default subject.
     *
     * @since 2.10.0
     */
    public function get_default_subject()
    {
        return __('An item from {site_title} order #{order_number} has shipped!', 'mwc-core');
    }

    /**
     * Gets the email default heading.
     *
     * @since 2.10.0
     */
    public function get_default_heading()
    {
        return __('Your package is on the way!', 'mwc-core');
    }

    /**
     * Gets the email default additional content.
     *
     * @since 2.10.0
     */
    public function get_default_additional_content()
    {
        return __('Please note that it may take some time for the carrier to update shipment tracking information.', 'mwc-core');
    }

    /**
     * Gets the content for the HTML version of the email.
     *
     * @since 2.10.0
     */
    public function get_content_html()
    {
        // allow merchants to overwrite the template by placing a copy of it the mwc subfolder inside their themes folder
        return wc_get_template_html('emails/customer-item-shipped.php', $this->getTemplateData(), 'mwc/', Configuration::get('mwc.directory').'/templates/woocommerce/');
    }

    /**
     * Gets the content for the plain version of the email.
     *
     * @since 2.10.0
     */
    public function get_content_plain()
    {
        // allow merchants to overwrite the template by placing a copy of it the mwc subfolder inside their themes folder
        return wc_get_template_html('emails/plain/customer-item-shipped.php', $this->getTemplateData(true), 'mwc/', Configuration::get('mwc.directory').'/templates/woocommerce/');
    }

    /**
     * Gets the data for the email templates.
     *
     * @since 2.10.0
     *
     * @param bool whether or not this the plain text version of the email
     * @return array
     */
    protected function getTemplateData(bool $plainText = false) : array
    {
        return [
            'order'              => $this->object,
            'email_heading'      => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'sent_to_admin'      => false,
            'plain_text'         => $plainText,
            'email'              => $this,
        ];
    }

    /**
     * Prepares the email and sends the message if enabled.
     *
     * @see WC_Email_Customer_Completed_Order::trigger()
     * @internal
     *
     * @param int $orderId the order ID
     * @param WC_Order|false $wcOrder WC order object
     * @throws Exception
     */
    public function trigger(int $orderId, $wcOrder = false)
    {
        $this->setup_locale();

        if ($orderId && ! is_a($wcOrder, 'WC_Order')) {
            $wcOrder = OrdersRepository::get($orderId);
        }

        if (is_a($wcOrder, 'WC_Order')) {
            $this->object = $wcOrder;
            $this->recipient = $this->object->get_billing_email();
            $this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
            $this->placeholders['{order_number}'] = $this->object->get_order_number();
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }
}
