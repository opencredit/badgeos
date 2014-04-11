<?php

namespace BadgeOS;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use BadgeOS\LogEntry;

class BadgeOSLogHandler extends AbstractProcessingHandler {
    private $statement;

    public function __construct() {
        parent::__construct($level = Logger::DEBUG, $bubble = TRUE);
    }

    protected function write(array $record) {
        $badgeos_settings       = get_option( 'badgeos_settings' );
        $user                   = get_userdata( $record['context']['user_id'] );
        $user_meta              = get_user_meta( $record['context']['user_id'] );
        $log                    = new LogEntry;
        $log->message           = $record['message'];
        $log->site_id           = $badgeos_settings['site_id'];
        $log->action            = $record['context']['action'];
        $log->object_id         = $record['context']['object_id'];

        if ( isset( $record['context']['object_id'] ) ) {
            $achievement        = get_post_meta( $record['context']['object_id'] );
            $log->points_earned = isset($achievement['_badgeos_points'][0]) ? $achievement['_badgeos_points'][0] : 0;
        }

        $log->total_points      = isset($user_meta['_badgeos_points'][0]) ? $user_meta['_badgeos_points'][0] : 0;
        $log->timestamp         = isset($record['context']['timestamp']) ? $record['context']['timestamp'] : $record['datetime']->format('Y-m-d H:i:s');
        $log->timezone          = isset($record['context']['timezone']) ? $record['context']['timezone'] : $record['datetime']->getTimezone()->getName(); 
        $log->user_id           = $record['context']['user_id'];
        $log->user_registered   = $user->data->user_registered; 
        $log->zip               = $user_meta['zip'][0];
        $log->save();
    }
}
