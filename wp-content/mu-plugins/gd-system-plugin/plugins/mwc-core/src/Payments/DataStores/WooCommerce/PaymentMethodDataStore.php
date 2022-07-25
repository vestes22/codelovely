<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\DataSources\WooCommerce\Adapters\CardPaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts\DataStoreContract;
use GoDaddy\WordPress\MWC\Payments\DataSources\WooCommerce\Adapters\BankAccountPaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * WooCommerce Payment Method datastore class.
 *
 * @since 2.10.0
 */
class PaymentMethodDataStore implements DataStoreContract
{
    /** @var string Data provider class name */
    protected $providerName;

    /** @var string Payment Tokens class name */
    protected $paymentTokens;

    /** @var DataSourceAdapterContract[] key value list of payment method adapters per type */
    protected $paymentMethodAdapters;

    /**
     * The WooCommerce Payment Method constructor.
     *
     * @since 2.10.0
     *
     * @param string $providerName
     * @param string $paymentTokens
     * @param array $paymentMethodAdapters
     */
    public function __construct(string $providerName, string $paymentTokens = '\WC_Payment_Tokens', array $paymentMethodAdapters = [])
    {
        $this->providerName = $providerName;
        $this->paymentTokens = $paymentTokens;

        $this->setPaymentMethodAdapters($paymentMethodAdapters);
    }

    /**
     * Sets the supported payment methods adapters.
     *
     * @since 2.10.0
     *
     * @param array $paymentMethodAdapters
     *
     * @return self
     */
    protected function setPaymentMethodAdapters(array $paymentMethodAdapters) : PaymentMethodDataStore
    {
        if (! ArrayHelper::exists($paymentMethodAdapters, 'CC')) {
            $paymentMethodAdapters['CC'] = CardPaymentMethodAdapter::class;
        }

        if (! ArrayHelper::exists($paymentMethodAdapters, 'eCheck')) {
            $paymentMethodAdapters['eCheck'] = BankAccountPaymentMethodAdapter::class;
        }

        $this->paymentMethodAdapters = $paymentMethodAdapters;

        return $this;
    }

    /**
     * Deletes method data from the data store.
     *
     * @since 2.10.0
     *
     * @param int $id
     *
     * @return bool
     * @throws BaseException
     */
    public function delete(int $id = null) : bool
    {
        if (null === $id) {
            throw new BaseException('Token ID is missing.');
        }

        call_user_func([$this->paymentTokens, 'delete'], $id);

        return true;
    }

    /**
     * Reads method data from the data store.
     *
     * @since 2.10.0
     *
     * @param int $id
     *
     * @return AbstractPaymentMethod
     * @throws BaseException
     */
    public function read(int $id = null) : AbstractPaymentMethod
    {
        if (null === $id) {
            throw new BaseException('Token ID is missing.');
        }

        $wooToken = call_user_func([$this->paymentTokens, 'get'], $id);
        if (null === $wooToken) {
            throw new BaseException('Token not found.');
        }

        $tokenAdapterClass = ArrayHelper::get($this->paymentMethodAdapters, $wooToken->get_type());
        if (null === $tokenAdapterClass) {
            throw new BaseException('No matching Payment method adapter found.');
        }

        return (new $tokenAdapterClass($wooToken))->convertFromSource();
    }

    /**
     * Saves method's data to the data store.
     *
     * @since 2.10.0
     *
     * @param AbstractPaymentMethod $method
     *
     * @return AbstractPaymentMethod
     * @throws BaseException
     */
    public function save(AbstractPaymentMethod $method = null) : AbstractPaymentMethod
    {
        if (null === $method) {
            throw new BaseException('Payment Method is missing.');
        }

        $matchingWooTokenType = $this->findMatchingWooTokenType(get_class($method));
        if (null === $matchingWooTokenType) {
            throw new BaseException('No matching Payment method adapter found.');
        }

        $wooPaymentTokenClass = $this->getMatchingWooTokenClass($matchingWooTokenType);
        $adapter = new $this->paymentMethodAdapters[$matchingWooTokenType](new $wooPaymentTokenClass());

        /** @var \WC_Payment_Token $convertedWooPaymentToken */
        $convertedWooPaymentToken = $adapter->convertToSource($method);
        $convertedWooPaymentToken->save();

        $method->setId($convertedWooPaymentToken->get_id());

        return $method;
    }

    /**
     * Finds a matching WooCommerce payment token type to the given native payment method.
     *
     * @since 2.10.0
     *
     * @param string $methodName
     *
     * @return string|null
     */
    protected function findMatchingWooTokenType(string $methodName)
    {
        $methodNameParts = explode('\\', $methodName);
        $className = array_pop($methodNameParts);

        foreach ($this->paymentMethodAdapters as $tokenType => $adapterName) {
            if (false !== strpos($adapterName, $className.'Adapter')) {
                return $tokenType;
            }
        }

        return null;
    }

    /**
     * Gets a matching WooCommerce payment token class to the given token type.
     *
     * @since 2.10.0
     *
     * @param string $tokenType
     *
     * @return string
     */
    protected function getMatchingWooTokenClass(string $tokenType) : string
    {
        return '\WC_Payment_Token_'.$tokenType;
    }
}
