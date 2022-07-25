<?php

namespace GoDaddy\WordPress\MWC\Shipping;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Providers\AbstractProvider;
use GoDaddy\WordPress\MWC\Common\Traits\HasProvidersTrait;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

/**
 * Shipping class.
 *
 * @since 0.1.0
 */
class Shipping
{
    use IsSingletonTrait;
    use HasProvidersTrait;

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
        load_plugin_textdomain('mwc-shipping', false, plugin_basename(dirname(__DIR__)).'/languages');

        $this->setProviders(Configuration::get('shipping.providers', []));
    }

    /**
     * Sets and instantiates given list of providers.
     *
     * @TODO consider moving this method to {@see AbstractProvider} {unfulvio 2021-06-17}
     *
     * @since 0.1.0
     *
     * @param string[] $providersClasses array of classes
     * @return self
     * @throws Exception
     */
    protected function setProviders(array $providersClasses) : Shipping
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
}
