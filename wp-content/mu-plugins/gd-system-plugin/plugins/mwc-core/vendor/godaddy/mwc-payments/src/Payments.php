<?php

namespace GoDaddy\WordPress\MWC\Payments;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;
use GoDaddy\WordPress\MWC\Payments\Providers\AbstractProvider;
use Exception;

/**
 * The main payments class.
 *
 * @since 0.1.0
 */
class Payments
{
    use IsSingletonTrait;

    /** @var AbstractProvider[] payment providers */
    protected $providers = [];

    /**
     * Sets up the providers from configuration.
     *
     * @since 0.1.0
     *
     * @throws Exception
     */
    public function __construct()
    {
        // load the textdomain
        load_plugin_textdomain('mwc-payments', false, plugin_basename(dirname(__DIR__)).'/languages');

        $this->setProviders(Configuration::get('payments.providers', []));
    }

    /**
     * Sets and instantiates given list of providers.
     *
     * @since 0.1.0
     *
     * @param array $providersClasses
     * @return Payments
     */
    protected function setProviders(array $providersClasses) : Payments
    {
        foreach ($providersClasses as $class) {
            if (false === is_subclass_of($class, AbstractProvider::class)) {
                continue;
            }

            /** @var AbstractProvider $provider */
            $provider = new $class();

            $this->providers[$provider->getName()] = $provider;
        }

        return $this;
    }

    /**
     * Get the providers.
     *
     * @since 0.1.0
     *
     * @return AbstractProvider[]
     */
    public function getProviders() : array
    {
        return $this->providers;
    }

    /**
     * Returns the requested provider, if found in the providers attribute.
     *
     * @since 0.1.0
     *
     * @param string $provider
     * @return AbstractProvider
     * @throws Exception
     */
    public function provider(string $provider) : AbstractProvider
    {
        $foundProvider = ArrayHelper::get($this->providers, $provider);

        if (empty($foundProvider)) {
            throw new Exception("The given provider {$provider} is not found.");
        }

        return $foundProvider;
    }
}
