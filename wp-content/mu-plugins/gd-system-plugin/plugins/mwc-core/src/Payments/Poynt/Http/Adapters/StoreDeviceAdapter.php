<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\Models\StoreDevice;

/**
 * The Store Devices adapter.
 *
 * @since 2.10.0
 */
class StoreDeviceAdapter implements DataSourceAdapterContract
{
    /** @var array */
    protected $source = [];

    /**
     * Store Device adapter constructor.
     *
     * @since 2.10.0
     *
     * @param StoreDevice|array $device internal or external representation of a device
     */
    public function __construct($device)
    {
        $this->source = $device;
    }

    /**
     * Converts an external StoreDevice into the internal StoreDevice model.
     *
     * @return StoreDevice
     */
    public function convertFromSource() : StoreDevice
    {
        return new StoreDevice([
            'id'              => ArrayHelper::get($this->source, 'deviceId'),
            'model'           => substr(ArrayHelper::get($this->source, 'serialNumber'), 0, 2),
            'name'            => ArrayHelper::get($this->source, 'name'),
            'serialNumber'    => ArrayHelper::get($this->source, 'serialNumber'),
            'status'          => ArrayHelper::get($this->source, 'status'),
            'storeId'         => ArrayHelper::get($this->source, 'storeId'),
            'type'            => ArrayHelper::get($this->source, 'type'),
        ]);
    }

    /**
     * Converts internal []StoreDevice to an external StoreDevice format.
     *
     * @return array|null
     */
    public function convertToSource() : array
    {
        return [
            'deviceId'        => ArrayHelper::get($this->source, 'deviceId'),
            'name'            => ArrayHelper::get($this->source, 'name'),
            'serialNumber'    => ArrayHelper::get($this->source, 'serialNumber'),
            'status'          => ArrayHelper::get($this->source, 'status'),
            'storeId'         => ArrayHelper::get($this->source, 'storeId'),
            'type'            => ArrayHelper::get($this->source, 'type'),
        ];
    }
}
