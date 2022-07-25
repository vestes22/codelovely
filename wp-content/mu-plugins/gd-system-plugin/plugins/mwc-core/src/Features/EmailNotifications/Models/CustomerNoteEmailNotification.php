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
 * Model for customer note email notification.
 */
class CustomerNoteEmailNotification extends EmailNotification implements OrderEmailNotificationContract
{
    use IsOrderEmailNotificationTrait;
    use IsCustomerEmailNotificationTrait;

    /** @var string */
    protected $id = 'customer_note';

    /** @var string[] */
    protected $categories = ['order'];

    /** @var string */
    protected $customerNote;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId())
            ->setLabel(__('Customer note', 'mwc-core'))
            ->setDescription(__('Sent to customer when you add a note to an order.', 'mwc-core'));
    }

    /**
     * Gets customer note.
     *
     * @return string|null
     */
    public function getCustomerNote()
    {
        return $this->customerNote;
    }

    /**
     * Sets customer note value.
     *
     * @param string $customerNote
     * @return CustomerNoteEmailNotification
     */
    public function setCustomerNote(string $customerNote) : CustomerNoteEmailNotification
    {
        $this->customerNote = $customerNote;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalData() : array
    {
        $order = $this->getOrder();

        return [
            'internal' => [
                'greeting' => $order ? $this->getGreeting($order) : '',
                'content' => $order ? $this->getMainContentHtml($order) : '',
            ],
        ];
    }

    /**
     * Gets the HTML for the main content section of the email.
     *
     * @param Order $order the order object associated with this email
     * @return string
     */
    protected function getMainContentHtml(Order $order) : string
    {
        ob_start(); ?>
        <p><?php esc_html_e('The following note has been added to your order:', 'mwc-core'); ?></p>
        <blockquote><?php echo wpautop(wptexturize(make_clickable($this->getCustomerNote()))); ?></blockquote>
        <p><?php esc_html_e('As a reminder, here are your order details:', 'mwc-core'); ?></p>
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
                ->setDefault(__('Note added to your {{site_title}} order from {{order_date}}', 'mwc-core')),
            $this->getPreviewTextSettingObject(),
        ];
    }

    /**
     * {@inheritdoc}
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
