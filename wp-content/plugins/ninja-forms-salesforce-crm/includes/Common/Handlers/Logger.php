<?php

namespace NinjaForms\Salesforce\Common\Handlers;
use NinjaForms\Salesforce\Common\Interfaces\NfLogger;
use NinjaForms\Salesforce\Common\Interfaces\NfLogHandler;

use NinjaForms\Salesforce\Common\VendorDist\Psr\Log\AbstractLogger;
/**
 * Logs data 
 */
class Logger extends AbstractLogger implements NfLogger
{

    /**
     * Log Handler array keyed on LogLevel
     * 
     * @var NfLogHandler[] */
    protected $logHandlers;


    /**
     * Record log using available handler at that level
     * @inheritDoc 
     */
    public function log($level,   $key, array $logEntryArray = []): void
    {
      if(isset($this->logHandlers[$level])){
        
        $logEntryArray['level'] = $level;
        
        $this->logHandlers[$level]->log($key,$logEntryArray);
      }
    }


    /**
     * Assigns a single handler to a given log level
     * 
     * @inheritDoc
     *
     * @param NfLogHandler $handler
     * @param string $logLevel
     * @return void
     */
    public function pushLogHandler(NfLogHandler $handler, string $logLevel): void
    {
        $this->logHandlers[$logLevel] = $handler;
    }
}
