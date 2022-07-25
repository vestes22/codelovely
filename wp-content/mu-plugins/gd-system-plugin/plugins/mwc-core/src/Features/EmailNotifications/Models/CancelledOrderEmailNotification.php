<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationWithRecipientsContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\OrderEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\EmailOrderHooksDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\OrderDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\SiteDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\HasRecipientsSettingTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsOrderEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;

/**
 * Model for email notifications.
 */
class CancelledOrderEmailNotification extends EmailNotification implements EmailNotificationWithRecipientsContract, OrderEmailNotificationContract
{
    use HasRecipientsSettingTrait;
    use IsOrderEmailNotificationTrait;

    /** @var string */
    protected $id = 'cancelled_order';

    /** @var string[] */
    protected $categories = ['admin'];

    /** @var bool */
    protected $sentToAdministrator = true;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId());
        $this->setLabel(__('Cancelled order', 'mwc-core'));
        $this->setDescription(__('Sent to chosen recipient(s) when an order is marked cancelled (if it was previously processing or on-hold).', 'mwc-core'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getInitialSettings() : array
    {
        return [
            $this->getEnabledSettingObject(),
            $this->getRecipientsSettingObject(),
            $this->getSubjectSettingObject()
                 ->setDefault(__('[{{site_title}}]: Order #{{order_number}} has been cancelled', 'mwc-core')),
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
        return [
            'internal' => [
                'content' => $this->getOrder() ? $this->getMainContent($this->getOrder()) : '',
            ],
        ];
    }

    /**
     * Gets the HTML for the main content section of the email.
     *
     * @param Order $order the order object associated with this email
     * @return string
     */
    protected function getMainContent(Order $order) : string
    {
        ob_start(); ?>

        <?php printf(
            /* translators: %1$s: Order number, %2$s: Customer full name.  */
            esc_html__('Notification to let you know &mdash; order #%1$s belonging to %2$s has been cancelled:', 'woocommerce'),
            esc_html($order->getNumber()),
            esc_html(trim($order->getBillingAddress()->getFirstName().' '.$order->getBillingAddress()->getLastName()))
        ); ?>
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
