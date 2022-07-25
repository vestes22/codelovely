<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\OrderEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\EmailOrderHooksDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\OrderDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\SiteDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsCustomerEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsOrderEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;

/**
 * Model for Order on Hold email notifications.
 */
class OrderOnHoldEmailNotification extends EmailNotification implements OrderEmailNotificationContract
{
    use IsOrderEmailNotificationTrait;
    use IsCustomerEmailNotificationTrait;

    /** @var string */
    protected $id = 'customer_on_hold_order';

    /** @var string[] */
    protected $categories = ['order'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId())
             ->setLabel(__('Order on-hold', 'mwc-core'))
             ->setDescription(__('Sent to customer when an order is on hold.', 'mwc-core'));
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
     * Gets data for the custom components that represent non-editable parts of the email notification.
     *
     * {@inheritdoc}
     */
    protected function getAdditionalData(): array
    {
        $order = $this->getOrder();

        return [
            'internal' => [
                'greeting' => $order ? $this->getGreeting($order) : '',
                'content' => $order ? $this->getMainContent() : '',
            ],
        ];
    }

    /**
     * Gets the content for the main content section of the email.
     *
     * @return string
     */
    protected function getMainContent() : string
    {
        ob_start(); ?>

        <?php esc_html_e('Thanks for your order. It’s on-hold until we confirm that payment has been received. In the meantime, here’s a reminder of what you ordered:', 'woocommerce'); ?>

        <?php

        return ob_get_clean();
    }

    /**
     * Gets the initial email notification data providers.
     *
     * By default this email notification uses the {@see SiteDataProvider}, {@see OrderDataProvider}
     * and {@see EmailOrderHooksDataProvider} data providers.
     *
     * @return DataProviderContract[]
     */
    protected function getInitialDataProviders() : array
    {
        return ArrayHelper::combine(parent::getInitialDataProviders(), [
            new OrderDataProvider($this),
            new EmailOrderHooksDataProvider($this),
        ]);
    }
}
