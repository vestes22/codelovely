<?php

return [
    /*
     *--------------------------------------------------------------------------
     * Shipping Providers
     *--------------------------------------------------------------------------
     *
     * The following array contains a list of shipping providers classes indexed
     * by name:
     *
     * ups => GoDaddy\WordPress\MWC\Shipping\Providers\UPS\UPSProvider::class
     */
    'providers' => [
        'australia-post' => GoDaddy\WordPress\MWC\Shipping\Providers\AustraliaPost\AustraliaPostProvider::class,
        'canada-post'    => GoDaddy\WordPress\MWC\Shipping\Providers\CanadaPost\CanadaPostProvider::class,
        'dhl'            => GoDaddy\WordPress\MWC\Shipping\Providers\DHL\DHLProvider::class,
        'fedex'          => GoDaddy\WordPress\MWC\Shipping\Providers\FedEx\FedExProvider::class,
        'ontrac'         => GoDaddy\WordPress\MWC\Shipping\Providers\OnTrac\OnTracProvider::class,
        'ups'            => GoDaddy\WordPress\MWC\Shipping\Providers\UPS\UPSProvider::class,
        'usps'           => GoDaddy\WordPress\MWC\Shipping\Providers\USPS\USPSProvider::class,
    ],
];
