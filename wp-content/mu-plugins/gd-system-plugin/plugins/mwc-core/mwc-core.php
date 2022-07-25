<?php
/**
 * Plugin name: MWC Core.
 */

namespace GoDaddy\WordPress\MWC\Core;

use Exception;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterAction;
use GoDaddy\WordPress\MWC\Core\Exceptions\CoreLoadingException;

require_once __DIR__.'/vendor/autoload.php';

try {
    RegisterAction::action()
        ->setGroup('plugins_loaded')
        ->setHandler(static function () {
            try {
                Package::getInstance();
            } catch (Exception $exception) {
                throw new CoreLoadingException("Failed to get core instance: {$exception->getMessage()}");
            }
        })
        ->execute();
} catch (Exception $exception) {
    // TODO: log the exception when a custom logger is added {CW 2021-02-22}
}
