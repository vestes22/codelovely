<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce;

use DateTime;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts\DataStoreContract;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Transactions\PaymentTransaction;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\BankAccountPaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\CardPaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\BankAccountPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\CardPaymentMethod;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\CaptureTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use WC_Order;
use WC_Payment_Token_CC;
use WC_Payment_Token_ECheck;

/**
 * A data store for WooCommerce transactions.
 *
 * @since 2.10.0
 */
class OrderTransactionDataStore implements DataStoreContract
{
    /** @var string Data provider class name. */
    protected $providerName;

    /** @var string[] Transaction properties to save. */
    protected $transactionProperties = [
        'createdAt',
        'remoteId',
        'remoteParentId',
        'totalAmount.amount',
        'paymentMethod.bin',
        'paymentMethod.brand.name',
        'paymentMethod.expirationMonth',
        'paymentMethod.expirationYear',
        'paymentMethod.id',
        'paymentMethod.lastFour',
        'paymentMethod.remoteId',
    ];

    /**
     * Order transaction data store constructor.
     *
     * @since 2.10.0
     *
     * @param string|null $providerName if not set, provider name will be read from order meta
     */
    public function __construct(string $providerName = null)
    {
        $this->providerName = $providerName;
    }

    /**
     * Deletes transaction data from the data store.
     *
     * @since 2.10.0
     *
     * @param string $remoteId
     * @return bool
     */
    public function delete(string $remoteId = null) : bool
    {
        // @TODO: deletes the transaction with the given ID {@nmolham 2021-04-27}
        return true;
    }

    /**
     * Reads the provider name from the data store.
     *
     * @param int $orderId woocommerce order ID
     * @return string|null
     * @throws Exception
     */
    public static function readProviderName(int $orderId)
    {
        if (! $order = OrdersRepository::get($orderId)) {
            return null;
        }

        return $order->get_meta('_mwc_transaction_provider_name') ?: $order->get_payment_method();
    }

    /**
     * Reads transaction data from the data store.
     *
     * @param int $orderId
     * @param string $type transaction type, e.g. 'payment', 'capture', 'refund', 'void', etc
     * @return AbstractTransaction
     * @throws BaseException|Exception
     */
    public function read(int $orderId = 0, string $type = '') : AbstractTransaction
    {
        $order = OrdersRepository::get($orderId);

        if (! $order) {
            throw new BaseException('Order not found');
        }

        if (! $this->providerName && ! ($this->providerName = static::readProviderName($orderId))) {
            throw new BaseException('Order is missing provider name');
        }

        $transaction = $this->getTransactionInstanceByType($type);

        $transaction->setProviderName($this->providerName);

        foreach (array_keys($transaction->toArray()) as $property) {
            $setProperty = 'set'.ucfirst($property);
            switch ($property) {
                case 'createdAt':
                    try {
                        $date = $order->get_meta($this->getPropertyMetaKey($type, $property));
                        $transaction->$setProperty(new DateTime($date));
                    } catch (Exception $e) {
                    }
                    break;
                case 'remoteId':
                case 'remoteParentId':
                    $value = $order->get_meta($this->getPropertyMetaKey($type, $property));
                    if (is_string($value)) {
                        $transaction->$setProperty($value);
                    }
                    break;
                case 'totalAmount':
                    $amount = $order->get_meta($this->getPropertyMetaKey($type, $property));
                    if (is_numeric($amount)) {
                        $transaction->$setProperty((new CurrencyAmount)->setAmount((int) $amount)->setCurrencyCode($order->get_currency()));
                    }
                    break;
                default:
                    continue 2;
            }
        }

        return $transaction;
    }

    /**
     * Saves transaction data to the data store.
     *
     * @param AbstractTransaction|null $transaction
     * @return AbstractTransaction
     * @throws Exception
     */
    public function save(AbstractTransaction $transaction = null) : AbstractTransaction
    {
        $order = $transaction->getOrder();

        if ($order) {
            $wcOrderInstance = OrdersRepository::get($order->getId());

            if ($wcOrderInstance) {
                if (! $this->providerName && ! ($this->providerName = static::readProviderName($order->getId()))) {
                    throw new BaseException('Order is missing provider name');
                }

                foreach ($this->transactionProperties as $property) {
                    $callable = $this->getPropertyAccessor($transaction, $property);

                    if (is_callable($callable)) {
                        $this->addOrderMeta($wcOrderInstance, $this->getPropertyMetaKey($transaction->getType(), $property), $this->formatProperty(call_user_func($callable)));
                    }
                }

                $adapter = $this->getWCPaymentMethodAdapter($transaction);

                $this->addOrderMeta($wcOrderInstance, '_mwc_transaction_provider_name', $this->providerName);

                if ($adapter) {
                    $token = $adapter->convertToSource($transaction->getPaymentMethod());
                    $wcOrderInstance->add_payment_token($token);
                }

                if (
                    ($transaction instanceof CaptureTransaction || ($transaction instanceof PaymentTransaction && ! $transaction->isAuthOnly()))
                    && $transaction->getStatus() instanceof ApprovedTransactionStatus
                ) {
                    $wcOrderInstance->update_meta_data('_mwc_payments_is_captured', 'yes');
                }

                if (
                    ($transaction instanceof RefundTransaction)
                    && $transaction->getStatus() instanceof ApprovedTransactionStatus
                ) {
                    $this->addOrderMeta($wcOrderInstance, $this->getPropertyMetaKey($transaction->getType(), 'remoteId'), $transaction->getRemoteId());
                }

                if ($transaction instanceof PaymentTransaction) {
                    $wcOrderInstance->set_transaction_id($transaction->getRemoteId());
                }

                $wcOrderInstance->save();
            }
        }

        return $transaction;
    }

    /**
     * Gets the appropriate WC PaymentMethodAdapter based on the PaymentMethod type.
     *
     * @param AbstractTransaction $transaction
     * @return BankAccountPaymentMethodAdapter|CardPaymentMethodAdapter
     */
    private function getWCPaymentMethodAdapter(AbstractTransaction $transaction)
    {
        if ($transaction->getPaymentMethod() instanceof CardPaymentMethod) {
            return new CardPaymentMethodAdapter(new WC_Payment_Token_CC());
        }

        if ($transaction->getPaymentMethod() instanceof BankAccountPaymentMethod) {
            return new BankAccountPaymentMethodAdapter(new WC_Payment_Token_Echeck());
        }

        return null;
    }

    /**
     * Produces an array of method calls that can be tested with is_callable.
     *
     * @param AbstractTransaction $transaction
     * @param string $property
     * @return array
     */
    private function getPropertyAccessor(AbstractTransaction $transaction, string $property): array
    {
        $pieces = explode('.', $property);

        if (count($pieces) > 1) {
            $parent = call_user_func([$transaction, 'get'.ucfirst($pieces[0])]);

            if (count($pieces) > 2 && is_callable([$parent, 'get'.ucfirst($pieces[1])])) {
                $parent = call_user_func([$parent, 'get'.ucfirst($pieces[1])]);
            }

            return [$parent, 'get'.ucfirst(end($pieces))];
        }

        return ArrayHelper::flatten([$transaction, 'get'.ucfirst($pieces[0])]);
    }

    /**
     * Formats properties that need formatting.
     *
     * @param $property
     * @return mixed
     */
    private function formatProperty($property)
    {
        if ($property instanceof DateTime) {
            return $property->format('c');
        }

        return $property;
    }

    /**
     *  Wraps the call to add_meta_data.
     *
     * @param WC_Order $order
     * @param string $key
     * @param $value
     * @return mixed
     */
    private function addOrderMeta(WC_Order $order, string $key, $value)
    {
        return $order->update_meta_data($key, $value);
    }

    /**
     * Gets a WooCommerce meta data key for a transaction property.
     *
     * @since 2.10.0
     *
     * @param string $type
     * @param string $property
     * @return string
     */
    private function getPropertyMetaKey(string $type, string $property) : string
    {
        $property = str_replace('.', '_', $property);

        return sprintf(
            '_%1$s_%2$s_%3$s',
            $this->providerName,
            $type,
            $property
        );
    }

    /**
     * Gets an object instance of a transaction by the given type.
     *
     * @since 2.10.0
     *
     * @param string $type
     * @return AbstractTransaction
     * @throws BaseException
     */
    private function getTransactionInstanceByType(string $type) : AbstractTransaction
    {
        if ('payment' === strtolower($type)) {
            $className = PaymentTransaction::class;
        } else {
            $nameSpace = '\\GoDaddy\\WordPress\\MWC\\Payments\\Models\\Transactions\\';
            $className = $nameSpace.ucfirst($type).'Transaction';
        }

        if (! class_exists($className)) {
            throw new BaseException($type ? sprintf('Cannot get an instance for "%s" transaction type', $type) : 'Undefined transaction type');
        }

        return new $className();
    }
}
