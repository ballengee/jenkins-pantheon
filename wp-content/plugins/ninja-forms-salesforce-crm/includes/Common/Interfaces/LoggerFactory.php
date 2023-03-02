<?php

namespace NinjaForms\Salesforce\Common\Interfaces;

use NinjaForms\Salesforce\Common\Interfaces\NfLogger;
use NinjaForms\Salesforce\Common\Interfaces\NfLogHandler;

interface LoggerFactory{


    /**
     * Provide a logger with desired log handlers
     *
     * @return NfLogger
     */
    public function getLogger( ): NfLogger;

    /**
     * Construct the desired log handler for debug level logs
     *
     * @return NfLogHandler
     */
    public function getDebugLogHandler(): NfLogHandler;

    /**
     * Construct the desired log handler for warning level logs
     *
     * @return NfLogHandler
     */
    public function getWarningHandler(): NfLogHandler;
}