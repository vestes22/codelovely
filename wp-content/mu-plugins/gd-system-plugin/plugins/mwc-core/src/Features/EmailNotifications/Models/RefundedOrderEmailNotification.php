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
 * Model for refunded order email notifications.
 */
class RefundedOrderEmailNotification extends EmailNotification implements OrderEmailNotificationContract
{
    use IsOrderEmailNotificationTrait;
    use IsCustomerEmailNotificationTrait;

    /** @var string */
    protected $id = 'customer_refunded_order';

    /** @var string[] */
    protected $categories = ['order'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId())
            ->setLabel(__('Fully refunded order', 'mwc-core'))
            ->setDescription(__('Sent to customers when their order is fully refunded.', 'mwc-core'));
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
                'content' => $this->getMainContent(),
            ],
        ];
    }

    /**
     * Gets the HTML for the main content section of the email.
     *
     * @return string
     */
    protected function getMainContent() : string
    {
        ob_start(); ?>
        <?php printf(
            /* translators: %s: Site title */
            esc_html__('Your order on %s has been refunded. There are more details below for your reference:', 'mwc-core'),
            esc_html($this->getSiteTitle())
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
                ->setDefault(__('Your {{site_title}} order #{{order_number}} has been refunded', 'mwc-core')),
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
