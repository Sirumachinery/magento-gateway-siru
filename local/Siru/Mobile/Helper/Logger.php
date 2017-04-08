<?php

/**
 * Class for sending log messages to siru log-file.
 */
class Siru_Mobile_Helper_Logger extends Mage_Core_Helper_Abstract {

    const LOG_FILE = 'siru_payment.log';

    public static function log($msg, $level = null)
    {
        Mage::log($msg, $level, self::LOG_FILE);
    }

}
