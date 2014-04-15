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
        $log = new LogEntry;

        foreach ($record['context'] as $key => $val) {
            $log->{$key} = $val;
        }

        $log->save();
    }
}
