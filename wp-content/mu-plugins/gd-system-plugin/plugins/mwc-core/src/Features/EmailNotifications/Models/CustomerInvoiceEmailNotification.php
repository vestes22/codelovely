<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\OrderEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\EmailOrderHooksDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\OrderDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsCustomerEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsOrderEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;

/**
 * Model for customer invoice email notification.
 */
class CustomerInvoiceEmailNotification extends EmailNotification implements OrderEmailNotificationContract
{
    use IsOrderEmailNotificationTrait;
    use IsCustomerEmailNotificationTrait;

    /** @var string */
    protected $id = 'customer_invoice';

    /** @var string[] */
    protected $categories = ['order'];

    /** @var bool */
    protected $manual = true;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId())
            ->setLabel(__('Customer invoice / order details', 'mwc-core'))
            ->setDescription(__('Emails containing order information and payment links sent manually.', 'mwc-core'));
    }

    /**
     * Gets additional data for this email notification.
     *
     * The data returned by this method will be merged with the data returned by
     * the registered data providers.
     *
     * @return array[]
     * @throws Exception
     */
    protected function getAdditionalData() : array
    {
        $order = $this->getOrder();

        return [
            'internal' => [
                'greeting' => $order ? $this->getGreeting($order) : '',
                'content' => $order ? $this->getMainContent($order) : '',
            ],
        ];
    }

    /**
     * Gets the main content section of the email.
     *
     * @param Order $order the order object associated with this email
     * @return string
     * @throws Exception
     */
    protected function getMainContent(Order $order) : string
    {
        ob_start(); ?>
        <?php printf(
            /* translators: %s Order date */
            esc_html__('Here are the details of your order placed on %s:', 'mwc-core'),
            esc_html($this->getOrderCreatedAtFormatted($order))
        ); ?>
        <?php

        return ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    protected function getInitialSettings() : array
    {
        return [
            $this->getEnabledSettingObject(),
            $this->getSubjectSettingObject()
                ->setDefault(__('Invoice for order #{{order_number}} on {{site_title}}', 'mwc-core')),
            $this->getPreviewTextSettingObject(),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function getInitialDataProviders() : array
    {
        return ArrayHelper::combine(parent::getInitialDataProviders(), [
            new OrderDataProvider($this),
            new EmailOrderHooksDataProvider($this),
        ]);
    }
}
