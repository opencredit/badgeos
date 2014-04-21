<?php

namespace BadgeOS;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use BadgeOS\BadgeOSLogHandler;
use DateTime;
use DateTimeZone;

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
        $this->pushHandler(new BadgeOSLogHandler());
    }

    public function pushHandler($handler, $severity = 'info') {
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
        $badgeos_settings       = get_option( 'badgeos_settings' );
        $user                   = get_userdata( $args['user_id'] );
        $user_meta              = get_user_meta( $args['user_id'] );

        if ( isset( $args['object_id'] ) ) { 
            $achievement            = get_post_meta( $args['object_id'] );
            $args['points_earned']  = isset($achievement['_badgeos_points'][0]) ? $achievement['_badgeos_points'][0] : 0;
        }   

        $args['total_points']       = isset($user_meta['_badgeos_points'][0]) ? $user_meta['_badgeos_points'][0] : 0;
        $args['user_registered']    = isset($user->data->user_registered) ? $user->data->user_registered : '0000-00-00 00:00:00'; 
        $args['zip']                = isset($user_meta['zip'][0]) ? $user_meta['zip'][0] : null;
        $args['site_id']            = gethostname();
        $args['message']            = $title;

        if (isset($args['timestamp'])) {
            $datetime = new DateTime($args['timestamp']);
        } else {
            $datetime = new DateTime;
        }

        // Set timezone to local time
        $tzstring = get_option('timezone_string');
        $timezone = new DateTimeZone($tzstring);
        $datetime->setTimezone($timezone);

        //$args['timestamp'] = $datetime->format('Y-m-d H:i:s');
        $args['timestamp'] = $datetime->format('c');
        $args['timezone']  = $datetime->getTimezone()->getName(); 

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
