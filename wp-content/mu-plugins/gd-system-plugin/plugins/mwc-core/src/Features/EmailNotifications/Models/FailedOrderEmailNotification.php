<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationWithRecipientsContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\OrderEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\EmailOrderHooksDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\OrderDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\HasRecipientsSettingTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsOrderEmailNotificationTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use InvalidArgumentException;

/**
 * The failed order email notification.
 */
class FailedOrderEmailNotification extends EmailNotification implements EmailNotificationWithRecipientsContract, OrderEmailNotificationContract
{
    use HasRecipientsSettingTrait;
    use IsOrderEmailNotificationTrait;

    /** @var string */
    protected $id = 'failed_order';

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
        $this->setLabel(__('Failed order', 'mwc-core'));
        $this->setDescription(__('Sent to chosen recipient(s) when an order is marked failed (if it was previously pending or on-hold).', 'mwc-core'));
    }

    /**
     * Gets data from the registered data providers.
     *
     * @return array
     * @throws Exception
     */
    public function getAdditionalData(): array
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
            /* translators: %1$s: Order number. %2$s: Customer full name. */
            esc_html__('Payment for order #%1$s from %2$s has failed. The order was as follows:', 'woocommerce'),
            esc_html($order->getNumber()),
            esc_html(trim($order->getBillingAddress()->getFirstName().' '.$order->getBillingAddress()->getLastName()))
        ); ?>
        <?php

        return ob_get_clean();
    }

    /**
     * Gets the email notification initial settings.
     *
     * @return SettingContract[]
     * @throws Exception|InvalidArgumentException
     */
    protected function getInitialSettings() : array
    {
        return [
            $this->getEnabledSettingObject(),
            $this->getRecipientsSettingObject(),
            $this->getSubjectSettingObject()
                ->setDefault(__('[{site_title}]: Order #{order_number} has failed', 'mwc-core')),
            $this->getPreviewTextSettingObject(),
        ];

        return ArrayHelper::combine(parent::getInitialSettings(), [$this->getRecipientsSettingObject()]);
    }

    /**
     * Gets the initial email notification data providers.
     *
     * @return DataProviderContract[] by default this includes an instance of {@see SiteDataProvider}
     *
     * @throws Exception
     */
    protected function getInitialDataProviders(): array
    {
        return ArrayHelper::combine(
            parent::getInitialDataProviders(),
            [
                new OrderDataProvider($this),
                new EmailOrderHooksDataProvider($this),
            ]
        );
    }
}
