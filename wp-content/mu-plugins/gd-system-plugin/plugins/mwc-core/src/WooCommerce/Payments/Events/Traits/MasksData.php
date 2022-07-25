<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

trait MasksData
{
    /**
     * Masks the given data keys.
     *
     * @param array $data
     * @param array $maskedKeys
     *
     * @return array
     */
    protected function maskData(array $data, array $maskedKeys) : array
    {
        foreach ($maskedKeys as $key) {
            if (! empty($data[$key])) {
                $data[$key] = ArrayHelper::set($data, $key, '*****');
            }
        }

        return $data;
    }
}
