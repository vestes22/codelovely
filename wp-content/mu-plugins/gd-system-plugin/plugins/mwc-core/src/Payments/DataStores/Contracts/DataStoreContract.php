<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\DataStores\Contracts;

/**
 * Interface DataStoreContract.
 *
 * @since 2.10.0
 */
interface DataStoreContract
{
    /**
     * Deletes a record from the data store.
     *
     * @return bool
     */
    public function delete() : bool;

    /**
     * Reads from the data store.
     *
     * @return mixed
     */
    public function read();

    /**
     * Saves a record to the data store.
     *
     * @return mixed
     */
    public function save();
}
