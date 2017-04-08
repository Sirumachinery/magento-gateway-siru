<?php

/**
 * Class for sending log messages to siru log-file.
 */
class Siru_Mobile_Helper_Logger extends Mage_Core_Helper_Abstract {

    const LOG_FILE = 'siru_payment.log';

    public function debug($msg)
    {
        $this->log($msg, Zend_Log::DEBUG);
    }

    public function info($msg)
    {
        $this->log($msg, Zend_Log::INFO);
    }

    public function error($msg)
    {
        $this->log($msg, Zend_Log::ERR);
    }

    public function log($msg, $level = null)
    {
        Mage::log($msg, $level, self::LOG_FILE);
    }

}
