<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Events\Subscribers\AbstractOrderNotesSubscriber;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\TransactionOrderNoteAdapter;
use GoDaddy\WordPress\MWC\Payments\Events\AbstractTransactionEvent;
use WC_Order;

/**
 * Transaction order notes subscriber event.
 *
 * @since 2.10.0
 */
class TransactionOrderNotesSubscriber extends AbstractOrderNotesSubscriber
{
    /** @var string the transaction order notes adapter class name */
    protected $adapter = TransactionOrderNoteAdapter::class;

    /**
     * Gets a WooCommerce order object.
     *
     * @param EventContract $event
     * @return WC_Order
     * @throws BaseException
     */
    protected function getOrder(EventContract $event) : WC_Order
    {
        if ($this->shouldHandle($event)) {
            $order = OrdersRepository::get((int) $event->getTransaction()->getOrder()->getId());

            if (! $order instanceof WC_Order) {
                throw new BaseException('Order not found');
            }

            return $order;
        }

        throw new BaseException('Invalid transaction event');
    }

    /**
     * Gets order notes.
     *
     * @since 2.10.0
     *
     * @param EventContract $event
     * @return string[]
     */
    protected function getNotes(EventContract $event) : array
    {
        return (new $this->adapter($event->getTransaction()))->convertFromSource();
    }

    /**
     * Determines whether it should handle the event.
     *
     * @since 2.10.0
     *
     * @param EventContract $event
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof AbstractTransactionEvent;
    }
}
