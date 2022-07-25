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
 * Model for order email notifications.
 */
class NewOrderEmailNotification extends EmailNotification implements EmailNotificationWithRecipientsContract, OrderEmailNotificationContract
{
    use HasRecipientsSettingTrait;
    use IsOrderEmailNotificationTrait;

    /** @var bool */
    protected $sentToAdministrator = true;

    /** @var string */
    protected $id = 'new_order';

    /** @var string[] */
    protected $categories = ['admin'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId())
            ->setLabel(__('New order', 'mwc-core'))
            ->setDescription(__('Sent to chosen recipient(s) when a new order is received.', 'mwc-core'));
    }

    /**
     * Gets additional data for this email notification.
     *
     * @return array
     */
    protected function getAdditionalData() : array
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
        $fullName = sprintf(
            /* translators: %1$s: Customer first name, %2$s: Customer last name.  */
            _x('%1$s %2$s', 'full name', 'mwc-core'),
            $order->getBillingAddress()->getFirstName(),
            $order->getBillingAddress()->getLastName()
        );

        ob_start(); ?>

        <?php printf(
            /* translators: %1$s: Customer full name.  */
            esc_html__('You’ve received the following order from %1$s:', 'mwc-core'),
            esc_html(trim($fullName))
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
                ->setDefault(__('[{{site_title}}]: New order #{{order_number}}', 'mwc-core')),
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
