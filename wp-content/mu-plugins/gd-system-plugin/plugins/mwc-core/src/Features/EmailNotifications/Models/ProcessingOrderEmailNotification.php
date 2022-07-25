<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\OrderEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\EmailOrderHooksDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\OrderDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsCustomerEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsOrderEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;

/**
 * Model for processing order email notifications.
 */
class ProcessingOrderEmailNotification extends EmailNotification implements OrderEmailNotificationContract
{
    use IsOrderEmailNotificationTrait;
    use IsCustomerEmailNotificationTrait;

    /** @var string */
    protected $id = 'customer_processing_order';

    /** @var string[] */
    protected $categories = ['order'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId())
            ->setLabel(__('Processing order', 'mwc-core'))
            ->setDescription(__('Sent to customer after a payment is made.', 'mwc-core'));
    }

    /**
     * Gets additional data for this email notification.
     *
     * @return array
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
     * Gets the content for the main content section of the email.
     *
     * @param Order $order the order object associated with this email
     * @return string
     */
    protected function getMainContent(Order $order) : string
    {
        ob_start(); ?>
        <?php printf(
            /* translators: %s: Order number */
            esc_html__('Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:', 'mwc-core'),
            esc_html($order->getNumber())
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
                ->setDefault(__('Your {{site_title}} order has been received!', 'mwc-core')),
            $this->getPreviewTextSettingObject(),
        ];
    }

    /**
     * Gets the initial email notification data providers.
     *
     * @return DataProviderContract[]
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
