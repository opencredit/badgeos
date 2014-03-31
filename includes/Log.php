<?php

namespace BadgeOS;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use BadgeOS\BadgeOSLogHandler;

interface Logging {
    public function pushHandlers();
}

class Log implements Logging {
    public $id = 'badgeos_log';
    protected $logger;

    function __construct() {
        $this->logger = new Logger($this->id);
        $this->pushHandlers();
    }

    /**
     * Defines what handlers to use for logging
     */
    public function pushHandlers() {
        $this->pushHandler(new BadgeOSLogHandler(), 'info');
    }

    public function pushHandler($handler, $severity) {
        $severity = $this->parseLevel($severity);
        $this->logger->pushHandler($handler, $severity);
    }

    /**
     * Write a log entry to the respected handler(s)
     *
     * @param string $title 
     * @param array $args 
     * An array of key/values to store in the log entry
     *
     */
    public function write($title, $args) {
        return $this->logger->addInfo($title, $args);
    }

    /**
     * Parse the string level into a Monolog constant.
     *
     * @param  string  $level
     * @return int
     *
     * @throws \InvalidArgumentException
     */
    protected function parseLevel($level)
    {
        switch ($level)
        {
            case 'debug':
                return Logger::DEBUG;

            case 'info':
                return Logger::INFO;

            case 'notice':
                return Logger::NOTICE;

            case 'warning':
                return Logger::WARNING;

            case 'error':
                return Logger::ERROR;

            case 'critical':
                return Logger::CRITICAL;

            case 'alert':
                return Logger::ALERT;

            case 'emergency':
                return Logger::EMERGENCY;

            default:
                throw new \InvalidArgumentException("Invalid log level.");
        }
    }
}
